<?php namespace Phroute\Authentic\User;

interface UserInterface {

    /**
     * Returns the user's ID.
     *
     * @return mixed
     */
    public function getId();

    /**
     * Returns the user's login (Email/Username).
     *
     * @return string
     */
    public function getLogin();

    /**
     * Returns the user's password (hashed).
     *
     * @return string
     */
    public function getPassword();

    /**
     * Sets the user's password (hashed).
     *
     * @param $hashedPassword
     */
    public function setPassword($hashedPassword);

    /**
     * Sets the user's remember me token.
     *
     * @param $token
     */
    public function setRememberToken($token);

    /**
     * Gets the user's remember me token.
     *
     *@return string  $persistCode
     */
    public function getRememberToken();

    /**
     * Sets the User's reset password token
     *
     * @param  string  $token
     */
    public function setResetPasswordToken($token);

    /**
     * Gets the user's reset password token.
     *
     * @return string
     */
    public function getResetPasswordToken();

    /**
     * Called after a user is logged in.
     */
    public function onLogin();

}
