<?php namespace Phroute\Authentic\Persistence;

class NativeSession implements PersistenceInterface {

    public function __construct()
    {
        session_start();
    }

    public function forget($name)
    {
        if(isset($_SESSION[$name]))
        {
            unset($_SESSION[$name]);
        }
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function get($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }
}
