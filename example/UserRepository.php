<?php namespace Application\Repository;

use Phroute\Authentic\User\UserRepositoryInterface;
use Phroute\Authentic\User\UserInterface;

/**
 * Class UserRepository
 * @package Application\Repository
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * @var DbLayer
     */
    private $dbConnection;

    /**
     * @param DbLayer $dbConnection Edit this and inject whatever Database layer you use, or just inject PDO and user raw queries
     */
    public function __construct(DbLayer $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }


    /**
     * @param $id
     * @return UserInterface
     */
    public function findById($id)
    {
        if($data = $this->dbConnection->where('id', '=', $id))
        {
            return new \User($this, $data);
        }
    }

    /**
     * @param $login
     * @return mixed
     * @throws UserInterface
     */
    public function findByLogin($login)
    {
        if($data = $this->dbConnection->where('email', '=', $login))
        {
            return new \User($this, $data);
        }
    }

    /**
     * Creates a user.
     *
     * @param array $credentials
     * @return UserInterface
     */
    public function registerUser(array $credentials)
    {
        $id = $this->dbConnection->insert($credentials);

        return $this->findById($id);
    }
}
