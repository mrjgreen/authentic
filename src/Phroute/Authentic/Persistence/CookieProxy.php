<?php namespace Phroute\Authentic\Persistence;

use DateTime;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class CookieProxy
 * @package Phroute\Authentic\Persistence
 */
class CookieProxy implements PersistenceInterface {

    /**
     * @var array
     */
    protected $queuedCookies = array();

    /**
     * @var array
     */
    protected $currentCookies;

    /**
     * @param array $cookies
     */
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * @param $name
     */
    public function forget($name)
    {
        $this->queuedCookies[] = new Cookie($name);
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->queuedCookies[] = new Cookie($name, $value, new DateTime('+10 years'));
    }

    /**
     * @param $name
     * @return null
     */
    public function get($name)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }

    /**
     * @return array
     */
    public function getQueuedCookies()
    {
        return $this->queuedCookies;
    }
}
