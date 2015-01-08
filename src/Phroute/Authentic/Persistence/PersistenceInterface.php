<?php namespace Phroute\Authentic\Persistence;

/**
 * Interface PersistenceInterface
 * @package Phroute\Authentic\Persistence
 */
interface PersistenceInterface
{
    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value);

    /**
     * @param $name
     * @return mixed
     */
    public function get($name);

    /**
     * @param $name
     */
    public function forget($name);
}