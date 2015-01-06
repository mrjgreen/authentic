<?php namespace Phroute\Authentic;

use Phroute\Authentic\Exception\AuthenticationException;
use Phroute\Authentic\Exception\LoginRequiredException;
use Phroute\Authentic\Exception\PasswordRequiredException;
use Phroute\Authentic\Exception\UserNotFoundException;
use Phroute\Authentic\Exception\WrongPasswordException;
use Phroute\Authentic\Persistence\NativeCookie;
use Phroute\Authentic\Persistence\NativeSession;
use Phroute\Authentic\User\UserRepositoryInterface;
use Phroute\Authentic\User\UserInterface;

class Authenticator {

    /**
     * @var string
     */
    protected $loginCredentialKey = 'email';

    /**
     * @var string
     */
    protected $passwordCredentialKey = 'password';

    /**
     * @var bool
     */
    protected $allowInactiveLogin = false;

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var PasswordHasher
     */
    protected $passwordHasher;

    /**
     * @var NamedPersistence
     */
    protected $session;

    /**
     * @var NamedPersistence
     */
    protected $cookie;

    /**
     * @var RandomStringGenerator
     */
    protected $randomStringGenerator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        NamedPersistenceInterface $session = null,
        NamedPersistenceInterface $cookie = null,
        PasswordHasher $passwordHasher = null
    )
    {
        $this->userRepository = $userRepository;

        $this->passwordHasher = $passwordHasher ?: new PasswordHasher();

        $this->session = $session ?: new NamedPersistence('auth.user', new NativeSession());

        $this->cookie = $cookie ?: new NamedPersistence('auth.remember', new NativeCookie());

        $this->randomStringGenerator = new RandomStringGenerator();
    }

    /**
     * Registers a user by giving the required credentials
     *
     * @param  array  $userDetails
     * @return UserInterface
     */
    public function register(array $userDetails)
    {
        $userDetails[$this->passwordCredentialKey] = $this->passwordHasher->hash($userDetails[$this->passwordCredentialKey]);

        $user = $this->userRepository->registerUser($userDetails);

        return $this->user = $user;
    }

    /**
     * Attempts to authenticate the given user
     * according to the passed credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return mixed
     */
    public function authenticate(array $credentials, $remember = false)
    {
        if (empty($credentials[$this->loginCredentialKey]))
        {
            throw new LoginRequiredException("The [$this->loginCredentialKey] attribute is required.");
        }

        if (empty($credentials[$this->passwordCredentialKey]))
        {
            throw new PasswordRequiredException("The [$this->passwordCredentialKey] attribute is required.");
        }

        if(!$user = $this->userRepository->findByLogin($credentials[$this->loginCredentialKey]))
        {
            throw new UserNotFoundException("The user [{$credentials[$this->loginCredentialKey]}] does not exist");
        }

        $password = $credentials[$this->passwordCredentialKey];

        if(!$this->passwordHasher->checkHash($password, $user->getPassword()))
        {
            throw new WrongPasswordException("Incorrect password provided");
        }

        if($this->passwordHasher->needsRehash($user->getPassword()))
        {
            $this->setPassword($user, $password);
        }

        $user->setResetPasswordToken(null);

        $this->storeAuthToken($user, $remember);

        // The user model can attach login handlers to the "onLogin" event.
        $user->onLogin();

        return $this->user = $user;
    }

    /**
     * @param UserInterface $user
     * @param string $newPassword Plain text password, to be hashed and set against user
     */
    public function setPassword(UserInterface $user, $newPassword)
    {
        $hash = $this->passwordHasher->hash($newPassword);

        $user->setPassword($hash);
    }

    /**
     * Attempts to reset a user's password by matching
     * the reset code generated with the user's.
     *
     * @param UserInterface $user
     * @param string $resetCode
     * @param string $newPassword Plain text password to be hashed
     * @return bool
     */
    public function resetPassword(UserInterface $user, $resetCode, $newPassword)
    {
        if ($user->getResetPasswordToken() === $resetCode)
        {
            $this->setPassword($user, $newPassword);

            return true;
        }

        return false;
    }

    /**
     * Try to find a user based on the login and set the reset token
     *
     * @param string $login
     * @return string
     */
    public function generateResetTokenForLogin($login)
    {
        if(!$user = $this->userRepository->findByLogin($login))
        {
            throw new UserNotFoundException("The user [$login] does not exist");
        }

        return $this->generateResetToken($user);
    }

    /**
     * Generate a reset token and set it against the user
     *
     * @param UserInterface $user
     * @return string
     */
    public function generateResetToken(UserInterface $user)
    {
        $user->setResetPasswordToken($token = $this->randomStringGenerator->generate());

        return $token;
    }

    /**
     * Check to see if the user is logged in and hasn't been suspended.
     *
     * @return bool
     */
    public function check()
    {
        if (!is_null($this->user))
        {
            return true;
        }

        $tokenArray = $this->readAuthToken();

        // The persistence token should be the user id and the remember me token in an array
        if (!is_array($tokenArray) or count($tokenArray) !== 2)
        {
            return false;
        }

        list($id, $persistenceToken) = array_values($tokenArray);

        if(!$user = $this->userRepository->findById($id))
        {
            return false;
        }

        if ($user->getRememberToken() !== $persistenceToken)
        {
            return false;
        }

        $this->user = $user;

        return true;
    }

    /**
     * @return bool|mixed
     */
    private function readAuthToken()
    {
        // Check the session
        if ($authTokenArray = $this->session->get())
        {
            return $authTokenArray;
        }

        // Check the cookie
        if($authCookie = $this->cookie->get())
        {
            return @json_decode($authCookie, true);
        }

        return false;
    }

    /**
     * Stores the auth token in the session
     *
     * @param UserInterface $user
     * @param bool $remember
     */
    private function storeAuthToken(UserInterface $user, $remember = false)
    {
        $user->setRememberToken($rememberMeToken = $this->randomStringGenerator->generate());

        // Create an array of data to persist to the session and / or cookie
        $toPersist = array($user->getId(), $rememberMeToken);

        // Set sessions
        $this->session->set($toPersist);

        if ($remember)
        {
            $this->cookie->set(json_encode($toPersist));
        }
    }

    /**
     *
     */
    public function refreshAuthToken()
    {
        if(!$this->check())
        {
            throw new AuthenticationException("No user logged in to refresh token.");
        }

        $this->storeAuthToken($this->user, (bool)$this->cookie->get());
    }

    /**
     * Logs the current user out.
     *
     * @return void
     */
    public function logout()
    {
        $this->user = null;

        $this->session->forget();
        $this->cookie->forget();
    }

    /**
     * Returns the current user being used by the Authenticator, if any.
     *
     * @param UserInterface $user
     * @return UserInterface
     */

    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Returns the current user being used by the Authenticator, if any.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        // We will lazily attempt to load our user
        if (is_null($this->user))
        {
            $this->check();
        }

        return $this->user;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setLoginCredentialName($name)
    {
        $this->loginCredentialKey = $name;

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setPasswordCredentialName($name)
    {
        $this->passwordCredentialKey = $name;

        return $this;
    }

    /**
     * @param RandomStringGenerator $randomStringGenerator
     */
    public function setRandomStringGenerator(RandomStringGenerator $randomStringGenerator)
    {
        $this->randomStringGenerator = $randomStringGenerator;
    }
}
