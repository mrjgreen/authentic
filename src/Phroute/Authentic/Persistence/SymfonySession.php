<?php namespace Phroute\Authentic\Persistence;

use Symfony\Component\HttpFoundation\Session\Session;

class SymfonySession implements PersistenceInterface {

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function forget($name)
    {
        $this->session->remove($name);
    }

    public function set($name, $value)
    {
        $this->session->set($name, $value);
    }

    public function get($name)
    {
        return $this->session->get($name);
    }
}
