<?php namespace Phroute\Authentic\Hash;

interface HasherInterface {

    /**
     * Hash string.
     *
     * @param  string $string
     * @return string
     */
    public function hash($string);

    /**
     * Check string against hashed string.
     *
     * @param  string  $string
     * @param  string  $hashedString
     * @return bool
     */
    public function checkHash($string, $hashedString);

    /**
     * Check if the password needs rehashing due to algorithm upgrade
     *
     * @param  string $hashedString
     * @return bool
     */
    public function needsRehash($hashedString);
}
