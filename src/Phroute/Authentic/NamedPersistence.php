<?php namespace Phroute\Authentic;

use Phroute\Authentic\Persistence\PersistenceInterface;

/**
 * Class NamedPersistence
 * @package Phroute\Authentic
 */
class NamedPersistence implements NamedPersistenceInterface {

    /**
     * @var
     */
    protected $name;

    /**
     * @var PersistenceInterface
     */
    protected $persistence;

    /**
     * @param $name
     * @param PersistenceInterface $persistence
     */
    public function __construct($name, PersistenceInterface $persistence)
    {
        $this->name = $name;

        $this->persistence = $persistence;
    }

    /**
     *
     */
    public function forget()
    {
        $this->persistence->forget($this->name);
    }

    /**
     * @param $value
     */
    public function set($value)
    {
        $this->persistence->set($this->name, $value);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->persistence->get($this->name);
    }
}
