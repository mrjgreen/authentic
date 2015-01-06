<?php namespace Phroute\AuthApp;

use Phroute\Authentic\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    private $db;

    public function __construct(\PDO $pdoDbConnection)
    {
        $this->db = $pdoDbConnection;
    }

    public function findByLogin($login)
    {

    }

    public function findById($id)
    {

    }
}