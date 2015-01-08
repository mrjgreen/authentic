<?php namespace Phroute\Authentic\Persistence;

/**
 * Class NativeSession
 * @package Phroute\Authentic\Persistence
 */
class NativeSession implements PersistenceInterface {

    /**
     * @param $name
     */
    public function forget($name)
    {
        if(isset($_SESSION[$name]))
        {
            unset($_SESSION[$name]);
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * @param $name
     * @return null
     */
    public function get($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }
}
