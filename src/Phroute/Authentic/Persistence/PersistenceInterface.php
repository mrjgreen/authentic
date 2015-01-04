<?php namespace Phroute\Authentic\Persistence;

interface PersistenceInterface
{
    public function set($name, $value);

    public function get($name);

    public function forget($name);
}