Authentic
=========

Authentic is a simple, framwork agnostic authentication library, designed to be flexible and easy to integrate with any PHP project.

~~~PHP
use Phroute\Authentic\Authenticator;

/**
 * Your application user repository
 * Implements:
 *    public function findById($id);
 *    public function findByLogin($login);
 *    public function registerUser(array $userDetails);
 */
$userRepository = new UserRepository();

$auth = new Authenticator($userRepository);
~~~

~~~PHP

use Phroute\Authentic\Exception\AuthenticationException;

$credentials = array(
    'email'     => 'email@example.com',
    'password'  => 'pa55w0rd'
);

$rememberMe = true;

try
{
   $auth->authenticate($credentials, $rememberMe);
}catch(AuthenticationException $e)
{
  return false; // Nope...
}

// Aaaaand we're in...
return true;
~~~
