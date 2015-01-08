<?php namespace Phroute\Authentic\Persistence;

/**
 * Class NativeCookie
 * @package Phroute\Authentic\Persistence
 */
class NativeCookie implements PersistenceInterface {

    /**
     * @param $name
     */
    public function forget($name)
    {
        setcookie($name, null, time() - 60 * 60 * 24 * 365 * 10);
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        setcookie($name, $value, time() + 60 * 60 * 24 * 365 * 10);
    }

    /**
     * @param $name
     * @return null
     */
    public function get($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }
}
