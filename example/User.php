<?php;

use Phroute\Authentic\User\UserInterface;
use Phroute\Authentic\User\UserRepositoryInterface;

class User implements UserInterface
{
    /**
     * @var
     */
    private $data;

    /**
     * @var UserRepositoryInterface
     */
    private $repository;

    /**
     * @param UserRepositoryInterface $repository
     * @param array $data
     */
    public function __construct(UserRepositoryInterface $repository, array $data)
    {
        $this->data = $data;

        $this->repository = $repository;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->data[$key];
    }

    /**
     * @param array $data
     */
    public function update(array $data)
    {
        $this->repository->update($this->getId(), $data);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->get('id');
    }

    /**
     * Returns the user's login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->get('email');
    }

    /**
     * Returns the user's password (hashed).
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->get('password');
    }


    public function setRememberToken($token)
    {
        $this->update(array('auth_token' => $token));
    }


    public function getRememberToken()
    {
        return $this->get('auth_token');
    }

    public function setPassword($newPassword)
    {
        $this->update(array(
            'password'              => $newPassword,
            'reset_password_token'   => null,
        ));
    }

    public function getResetPasswordToken()
    {
        return $this->get('reset_password_token');
    }

    public function setResetPasswordToken($token)
    {
        $this->update(array('reset_password_token' => $token));
    }

    public function onLogin()
    {
        $this->update(array('last_login' => $this->repository->timestamp()));
    }
}
