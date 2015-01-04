<?php namespace Phroute\Authentic;

interface UserRepositoryInterface {

    public function findById($id);

    public function findByLogin($login);

}
