<?php namespace Phroute\Authentic\Persistence;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class SymfonySession
 * @package Phroute\Authentic\Persistence
 */
class SymfonySession implements PersistenceInterface {

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param $name
     */
    public function forget($name)
    {
        $this->session->remove($name);
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->session->set($name, $value);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->session->get($name);
    }
}
