<?php namespace Phroute\Authentic;

interface UserInterface {

    /**
     * Returns the user's ID.
     *
     * @return mixed
     */
    public function getId();

    /**
     * Returns the user's login.
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
     * Check if the user is activated.
     *
     * @return bool
     */
    public function isActivated();

    /**
     * Gets a code for when the user is
     * persisted to a cookie or session which
     * identifies the user.
     *
     * @return string
     */
    public function getPersistCode();

    /**
     * Checks the given persist code.
     *
     * @param  string  $persistCode
     * @return bool
     */
    public function checkPersistCode($persistCode);

    /**
     * Get an activation code for the given user.
     *
     * @return string
     */
    public function getActivationCode();

    /**
     * Attempts to activate the given user by checking
     * the activate code. If the user is activated already,
     * an Exception is thrown.
     *
     * @param  string  $activationCode
     * @return bool
     */
    public function attemptActivation($activationCode);

    /**
     * Get a reset password code for the given user.
     *
     * @return string
     */
    public function getResetPasswordCode();

    /**
     * Checks if the provided user reset password code is
     * valid without actually resetting the password.
     *
     * @param  string  $resetCode
     * @return bool
     */
    public function checkResetPasswordCode($resetCode);

    /**
     * Attempts to reset a user's password by matching
     * the reset code generated with the user's.
     *
     * @param  string  $resetCode
     * @param  string  $newPassword
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword);

    /**
     * Wipes out the data associated with resetting
     * a password.
     *
     * @return void
     */
    public function clearResetPassword();

    /**
     * Records a login for the user.
     *
     * @return void
     */
    public function recordLogin();

}
