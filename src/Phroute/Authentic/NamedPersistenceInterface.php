<?php namespace Phroute\Authentic;

interface NamedPersistenceInterface {

    public function forget();

    public function get();

    public function set($value);
}
