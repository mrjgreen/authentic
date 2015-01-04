<?php namespace Phroute\Authentic;

use Phroute\Authentic\Persistence\PersistenceInterface;

class NamedPersistence implements NamedPersistenceInterface {

    protected $name;

    protected $persistence;

    public function __construct($name, PersistenceInterface $persistence)
    {
        $this->name = $name;

        $this->persistence = $persistence;
    }

    public function forget()
    {
        $this->persistence->forget($this->name);
    }

    public function set($value)
    {
        $this->persistence->set($this->name, $value);
    }

    public function get()
    {
        return $this->persistence->get($this->name);
    }
}
