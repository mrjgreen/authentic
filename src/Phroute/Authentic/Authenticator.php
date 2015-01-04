<?php namespace Phroute\Authentic;

use Phroute\Authentic\Exception\LoginRequiredException;
use Phroute\Authentic\Exception\PasswordRequiredException;
use Phroute\Authentic\Exception\UserNotActivatedException;
use Phroute\Authentic\Exception\UserNotFoundException;
use Phroute\Authentic\Exception\WrongPasswordException;
use Phroute\Authentic\Persistence\NativeCookie;
use Phroute\Authentic\Persistence\NativeSession;

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
    protected $allowInactivedLogin = false;

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
    }

    /**
     * Registers a user by giving the required credentials
     * and an optional flag for whether to activate the user.
     *
     * @param  array  $credentials
     * @param  bool   $activate
     * @return UserInterface
     */
    public function register(array $credentials, $activate = false)
    {
        $credentials[$this->passwordCredentialKey] = $this->passwordHasher->hash($credentials[$this->passwordCredentialKey]);

        $user = $this->userRepository->create($credentials);

        if ($activate)
        {
            $user->attemptActivation($user->getActivationCode());
        }

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
            $this->updatePassword($user, $password);
        }

        $user->clearResetPassword();

        $this->login($user, $remember);

        return $this->user;
    }

    /**
     * @param UserInterface $user
     * @param $newPassword
     */
    public function updatePassword(UserInterface $user, $newPassword)
    {
        $hash = $this->passwordHasher->hash($newPassword);

        $user->setPassword($hash);
    }

    /**
     * Attempts to reset a user's password by matching
     * the reset code generated with the user's.
     *
     * @param UserInterface $user
     * @param $resetCode
     * @param $newPassword
     * @return bool
     */
    public function resetPassword(UserInterface $user, $resetCode, $newPassword)
    {
        if ($user->checkResetPasswordCode($resetCode))
        {
            $this->updatePassword($user, $newPassword);

            return true;
        }

        return false;
    }

    /**
     * Check to see if the user is logged in and activated, and hasn't been banned or suspended.
     *
     * @return bool
     */
    public function check()
    {
        if (is_null($this->user))
        {
            // Check session first, follow by cookie
            if ( ! $userArray = $this->session->get() and ! $userArray = $this->cookie->get())
            {
                return false;
            }

            // Now check our user is an array with two elements,
            // the username followed by the persist code
            if ( ! is_array($userArray) or count($userArray) !== 2)
            {
                return false;
            }

            list($id, $persistCode) = $userArray;

            if(!$user = $this->userRepository->findById($id))
            {
                return false;
            }

            // Great! Let's check the session's persist code
            // against the user. If it fails, somebody has tampered
            // with the cookie / session data and we're not allowing
            // a login
            if ( ! $user->checkPersistCode($persistCode))
            {
                return false;
            }

            // Now we'll set the user property on the Authenticator
            $this->user = $user;
        }

        // Let's check our cached user is indeed activated
        if ( ! $user = $this->getUser())
        {
            return false;
        }

        // Let's check our cached user is indeed activated
        if ( ! $this->allowInactivedLogin && ! $user->isActivated())
        {
            return false;
        }

        return true;
    }

    /**
     * Logs in the given user and sets properties
     * in the session.
     *
     * @param UserInterface $user
     * @param bool $remember
     */
    public function login(UserInterface $user, $remember = false)
    {
        if ( ! $this->allowInactivedLogin && ! $user->isActivated())
        {
            $login = $user->getLogin();
            throw new UserNotActivatedException("Cannot login user [$login] as they are not activated.");
        }

        $this->user = $user;

        // Create an array of data to persist to the session and / or cookie
        $toPersist = array($user->getId(), $user->getPersistCode());

        // Set sessions
        $this->session->set($toPersist);

        if ($remember)
        {
            $this->cookie->set($toPersist);
        }

        // The user model can attach any handlers
        // to the "recordLogin" event.
        $user->recordLogin();
    }

    /**
     * Alias for logging in and remembering.
     *
     * @param UserInterface $user
     */
    public function loginAndRemember(UserInterface $user)
    {
        $this->login($user, true);
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
     *
     */
    public function allowInactivedLogin()
    {
        $this->allowInactivedLogin = true;

        return $this;
    }
}
