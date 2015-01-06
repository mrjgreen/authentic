<?php namespace Phroute\Authentic\User;

interface UserRepositoryInterface {

    /**
     * @param $id
     * @return UserInterface
     */
    public function findById($id);

    /**
     * @param $login
     * @return UserInterface
     */
    public function findByLogin($login);

    /**
     * @param array $userDetails
     * @return mixed
     */
    public function registerUser(array $userDetails);

}
