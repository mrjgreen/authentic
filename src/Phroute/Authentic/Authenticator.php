<?php namespace Phroute\Authentic;

use Phroute\Authentic\Exception\AuthenticationException;
use Phroute\Authentic\Exception\LoginRequiredException;
use Phroute\Authentic\Exception\PasswordRequiredException;
use Phroute\Authentic\Exception\UserExistsException;
use Phroute\Authentic\Exception\UserNotFoundException;
use Phroute\Authentic\Exception\WrongPasswordException;
use Phroute\Authentic\Hash\HasherInterface;
use Phroute\Authentic\Hash\PasswordHasher;
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
     * @var UserInterface
     */
    protected $user;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var HasherInterface
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
        HasherInterface $passwordHasher = null
    )
    {
        $this->userRepository = $userRepository;

        $this->passwordHasher = $passwordHasher ?: new PasswordHasher();

        $this->session = $session ?: new NamedPersistence('auth.user', new NativeSession());

        $this->cookie = $cookie ?: new NamedPersistence('auth_remember', new NativeCookie());

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
        $email = $userDetails[$this->loginCredentialKey];

        if($this->userRepository->findByLogin($email))
        {
            throw new UserExistsException("The user '$email' already exists.");
        }

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

        $user = $this->findUserByLogin($credentials[$this->loginCredentialKey]);

        $this->checkPassword($user, $credentials[$this->passwordCredentialKey]);

        $user->setResetPasswordToken(null);

        $this->storeAuthToken($user, $remember);

        // The user model can attach login handlers to the "onLogin" event.
        $user->onLogin();

        return $this->user = $user;
    }

    /**
     * @param UserInterface $user
     * @param $password
     */
    public function checkPassword(UserInterface $user, $password)
    {
        if(!$this->passwordHasher->checkHash($password, $user->getPassword()))
        {
            throw new WrongPasswordException("Incorrect password provided");
        }

        if($this->passwordHasher->needsRehash($user->getPassword()))
        {
            $this->setPassword($user, $password);
        }
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
     * @param string $login
     * @param string $resetCode
     * @param string $newPassword Plain text password to be hashed
     * @return bool
     */
    public function resetPasswordForLogin($login, $resetCode, $newPassword)
    {
        return $this->resetPassword($this->findUserByLogin($login), $resetCode, $newPassword);
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
        if ($this->constTimeComparison($user->getResetPasswordToken(), $resetCode))
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
        return $this->generateResetToken($this->findUserByLogin($login));
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

        if ($this->constTimeComparison($user->getRememberToken(), $persistenceToken))
        {
            $this->user = $user;

            return true;
        }

        return false;
    }

    /**
     * Force an update of the remember me token. Update the user, the session and the cookie.
     * This should be called each request to avoid replay attacks
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
     * Generate a random string for authentication tokens
     *
     * @param RandomStringGenerator $randomStringGenerator
     */
    public function setRandomStringGenerator(RandomStringGenerator $randomStringGenerator)
    {
        $this->randomStringGenerator = $randomStringGenerator;
    }

    /**
     * Read the auth token from the session or cookie
     *
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
     * @param $login
     * @return UserInterface
     */
    private function findUserByLogin($login)
    {
        if(!$user = $this->userRepository->findByLogin($login))
        {
            throw new UserNotFoundException("The user [$login] does not exist");
        }

        return $user;
    }

    /**
     * @param $string1
     * @param $string2
     * @return bool
     */
    private function constTimeComparison($string1, $string2)
    {
        if(defined('hash_compare'))
        {
            return hash_compare($string1, $string2);
        }

        if (strlen($string1) !== strlen($string2)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($string1); $i++) {
            $result |= ord($string1[$i]) ^ ord($string2[$i]);
        }

        return 0 === $result;
    }
}
