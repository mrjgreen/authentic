<?php namespace Phroute\Authentic\Persistence;

class NativeCookie implements PersistenceInterface {

    public function forget($name)
    {
        setcookie($name, null, time() - 60 * 60 * 24 * 365 * 10);
    }

    public function set($name, $value)
    {
        setcookie($name, $value, time() + 60 * 60 * 24 * 365 * 10);
    }

    public function get($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }
}
