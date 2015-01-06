<?php namespace Phroute\Authentic\Persistence;

class CookieProxy implements PersistenceInterface {

    /**
     * @var array
     */
    protected $queuedCookies = array();

    /**
     * @var array
     */
    protected $currentCookies;

    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function forget($name)
    {
        $this->store($name, null, -(60 * 60 * 24 * 365 * 10));
    }

    public function set($name, $value)
    {
        $this->store($name, $value, (60 * 60 * 24 * 365 * 10));
    }

    public function get($name)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }

    public function getQueuedCookies()
    {
        return $this->queuedCookies;
    }

    private function store($name, $value, $time)
    {
        $this->queuedCookies[] = array($name, $value, $this->getTime($time));
    }

    public function getTime($time)
    {
        return time() + $time;
    }
}
