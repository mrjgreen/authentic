<?php namespace Phroute\Authentic;

/**
 * Interface NamedPersistenceInterface
 * @package Phroute\Authentic
 */
interface NamedPersistenceInterface {

    /**
     *
     */
    public function forget();

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param $value
     */
    public function set($value);
}
