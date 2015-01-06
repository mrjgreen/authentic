<?php namespace Phroute\AuthApp;

use Phroute\Authentic\UserInterface;

class User implements UserInterface
{
    private $userData;

    public function __construct($userData)
    {
        $this->userData = $userData;
    }

}