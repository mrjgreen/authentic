<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\Authenticator;

class AuthenticatorTest extends \PHPUnit_Framework_TestCase {

	protected $userProvider;

	protected $hasher;

	protected $session;

	protected $cookie;

	protected $authentic;

	/**
	 * Setup resources and dependencies.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->authentic = new Authenticator(
			$this->userProvider     = m::mock('Phroute\Authentic\UserRepositoryInterface'),
			$this->session          = m::mock('Phroute\Authentic\NamedPersistenceInterface'),
			$this->cookie           = m::mock('Phroute\Authentic\NamedPersistenceInterface'),
			$this->hasher    		= m::mock('Phroute\Authentic\PasswordHasher')
		);
	}

	/**
	 * Close mockery.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\UserNotActivatedException
	 */
	public function testLoggingInUnactivatedUser()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');
		$user->shouldReceive('isActivated')->once()->andReturn(false);
		$user->shouldReceive('getLogin')->once()->andReturn('foo');

		$this->authentic->login($user);
	}

	public function testLoggingInUser()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');
		$user->shouldReceive('isActivated')->once()->andReturn(true);
		$user->shouldReceive('getId')->once()->andReturn('foo');
		$user->shouldReceive('getPersistCode')->once()->andReturn('persist_code');
		$user->shouldReceive('recordLogin')->once();

		$this->session->shouldReceive('set')->with(array('foo', 'persist_code'))->once();

		$this->authentic->login($user);
	}

	public function testLoggingInUserWithCookie()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');
		$user->shouldReceive('isActivated')->once()->andReturn(true);
		$user->shouldReceive('getId')->once()->andReturn('foo');
		$user->shouldReceive('getPersistCode')->once()->andReturn('persist_code');
		$user->shouldReceive('recordLogin')->once();

		$this->session->shouldReceive('set')->with(array('foo', 'persist_code'))->once();
		$this->cookie->shouldReceive('set')->with(json_encode(array('foo', 'persist_code')))->once();

		$this->authentic->login($user, true);
	}

	public function testLoggingInAndRemembering()
	{
		$authentic = m::mock('Phroute\Authentic\Authenticator[login]', array(
			$this->userProvider,
			$this->session,
			$this->cookie,
			$this->hasher,
		));

		$authentic->shouldReceive('login')->with($user = m::mock('Phroute\Authentic\UserInterface'), true)->once();
		$authentic->loginAndRemember($user);
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\LoginRequiredException
	 */
	public function testAuthenticatingUserWhenLoginIsNotProvided()
	{
		$credentials = array();

		$this->authentic->authenticate($credentials);
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\PasswordRequiredException
	 */
	public function testAuthenticatingUserWhenPasswordIsNotProvided()
	{
		$credentials = array(
			'email' => 'foo@bar.com',
		);

		$this->authentic->authenticate($credentials);
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\UserNotFoundException
	 */
	public function testAuthenticatingUserWhereTheUserDoesNotExist()
	{
		$credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'baz_bat',
		);

		$this->userProvider->shouldReceive('findByLogin')
			->with($credentials['email'])->once()
			->andReturn(false);

		$this->authentic->authenticate($credentials);
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\WrongPasswordException
	 */
	public function testAuthenticatingUserWithWrongPassword()
	{
		$this->authentic = m::mock('Phroute\Authentic\Authenticator[login]', array(
			$this->userProvider,
			$this->session,
			$this->cookie,
			$this->hasher,
		));

		$credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'baz_bat',
		);

		$user = $this->getMock('Phroute\Authentic\UserInterface');

		$this->hasher->shouldReceive('checkHash')->andReturn(false);

		$this->userProvider->shouldReceive('findByLogin')->with($credentials['email'])->once()->andReturn($user);

		$this->authentic->authenticate($credentials);
	}

	public function testAuthenticatingUser()
	{
		$this->authentic = m::mock('Phroute\Authentic\Authenticator[login]', array(
			$this->userProvider,
			$this->session,
			$this->cookie,
			$this->hasher,
		));

		$credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'baz_bat',
		);

		$hashedPassword = (new \Phroute\Authentic\PasswordHasher())->hash($credentials['password']);

		$user = m::mock('Phroute\Authentic\UserInterface');

		$user->shouldReceive('getPassword')->andReturn($hashedPassword);
		$this->hasher->shouldReceive('checkHash')
			->with($credentials['password'], $hashedPassword)
			->andReturn(true);

		$this->hasher->shouldReceive('needsRehash')->with($hashedPassword)->andReturn(false);

		$this->userProvider->shouldReceive('findByLogin')->with($credentials['email'])->once()->andReturn($user);

		$user->shouldReceive('clearResetPassword')->once();

		$this->authentic->shouldReceive('login')->with($user, false)->once();
		$this->authentic->authenticate($credentials);
	}

	public function testCheckLoggingOut()
	{
		$this->authentic->setUser(m::mock('Phroute\Authentic\UserInterface'));
		$this->session->shouldReceive('get')->once();
		$this->session->shouldReceive('forget')->once();
		$this->cookie->shouldReceive('get')->once();
		$this->cookie->shouldReceive('forget')->once();

		$this->authentic->logout();
		$this->assertNull($this->authentic->getUser());
	}

	public function testCheckingUserWhenUserIsSetAndActivated()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');

		$user->shouldReceive('isActivated')->once()->andReturn(true);

		$this->authentic->setUser($user);
		$this->assertTrue($this->authentic->check());
	}

	public function testCheckingUserWhenUserIsSetAndNotActivated()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');
		$user->shouldReceive('isActivated')->once()->andReturn(false);

		$this->authentic->setUser($user);
		$this->assertFalse($this->authentic->check());
	}

	public function testCheckingUserChecksSessionFirst()
	{
		$this->session->shouldReceive('get')->once()->andReturn(array('foo', 'persist_code'));
		$this->cookie->shouldReceive('get')->never();

		$this->userProvider->shouldReceive('findById')->andReturn($user = m::mock('Phroute\Authentic\UserInterface'));

		$user->shouldReceive('checkPersistCode')->with('persist_code')->once()->andReturn(true);
		$user->shouldReceive('isActivated')->once()->andReturn(true);

		$this->assertTrue($this->authentic->check());
	}

	public function testCheckingUseFailsIfNoUserFound()
	{
		$this->session->shouldReceive('get')->once()->andReturn(array('foo', 'persist_code'));

		$this->userProvider->shouldReceive('findById')->andReturn(false);

		$this->assertFalse($this->authentic->check());
	}

	public function testCheckingUseFailsIfWrongPersistCode()
	{
		$this->session->shouldReceive('get')->once()->andReturn(array('foo', 'persist_code'));

		$this->userProvider->shouldReceive('findById')->andReturn($user = m::mock('Phroute\Authentic\UserInterface'));

		$user->shouldReceive('checkPersistCode')->with('persist_code')->once()->andReturn(false);

		$this->assertFalse($this->authentic->check());
	}

	public function testCheckingUserChecksSessionFirstAndThenCookie()
	{
		$this->session->shouldReceive('get')->once();
		$this->cookie->shouldReceive('get')->once()->andReturn(json_encode(array('foo', 'persist_code')));

		$this->userProvider->shouldReceive('findById')->andReturn($user = m::mock('Phroute\Authentic\UserInterface'));

		$user->shouldReceive('checkPersistCode')->with('persist_code')->once()->andReturn(true);
		$user->shouldReceive('isActivated')->once()->andReturn(true);

		$this->assertTrue($this->authentic->check());
	}

	public function testCheckingUserReturnsFalseIfNoArrayIsReturned()
	{
		$this->session->shouldReceive('get')->once()->andReturn('we_should_never_return_a_string');

		$this->assertFalse($this->authentic->check());
	}

	public function testCheckingUserReturnsFalseIfIncorrectArrayIsReturned()
	{
		$this->session->shouldReceive('get')->once()->andReturn(array('we', 'should', 'never', 'have', 'more', 'than', 'two'));

		$this->assertFalse($this->authentic->check());
	}

	public function testCheckingUserWhenNothingIsFound()
	{
		$this->session->shouldReceive('get')->once()->andReturn(null);

		$this->cookie->shouldReceive('get')->once()->andReturn(null);

		$this->assertFalse($this->authentic->check());
	}

	public function testRegisteringUser()
	{
		$credentialsExpected = $credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'sdf_sdf',
		);

		$user = m::mock('Phroute\Authentic\UserInterface');
		$user->shouldReceive('getActivationCode')->never();
		$user->shouldReceive('attemptActivation')->never();
		$user->shouldReceive('isActivated')->once()->andReturn(false);

		$this->hasher->shouldReceive('hash')
			->with($credentials['password'])
			->andReturn('abcdefg');

		$credentialsExpected['password'] = 'abcdefg';

		$this->userProvider->shouldReceive('create')->with($credentialsExpected)->once()->andReturn($user);

		$this->assertEquals($user, $registeredUser = $this->authentic->register($credentials));
		$this->assertFalse($registeredUser->isActivated());
	}

	public function testRegisteringUserWithActivationDone()
	{
		$credentialsExpected = $credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'sdf_sdf',
		);

		$user = m::mock('Phroute\Authentic\UserInterface');
		$user->shouldReceive('getActivationCode')->once()->andReturn('activation_code_here');
		$user->shouldReceive('attemptActivation')->with('activation_code_here')->once();
		$user->shouldReceive('isActivated')->once()->andReturn(true);

		$this->hasher->shouldReceive('hash')
			->with($credentials['password'])
			->andReturn('abcdefg');

		$credentialsExpected['password'] = 'abcdefg';

		$this->userProvider->shouldReceive('create')->with($credentialsExpected)->once()->andReturn($user);

		$this->assertEquals($user, $registeredUser = $this->authentic->register($credentials, true));
		$this->assertTrue($registeredUser->isActivated());
	}

	public function testGetUserWithCheck()
	{
		$authentic = m::mock('Phroute\Authentic\Authenticator[check]', array(
			$this->userProvider,
			$this->session,
			$this->cookie,
			$this->hasher,
		));

		$authentic->shouldReceive('check')->once();
		$authentic->getUser();
	}

	public function testResetPassword()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');

		$user->shouldReceive('checkResetPasswordCode')->with('reset_code')->andReturn(true);
		$user->shouldReceive('setPassword')->with('hashed_password');

		$this->hasher->shouldReceive('hash')->with('foo_bah')->andReturn('hashed_password');

		$this->authentic->resetPassword($user, 'reset_code', 'foo_bah');
	}

	public function testResetPasswordFailure()
	{
		$user = m::mock('Phroute\Authentic\UserInterface');

		$user->shouldReceive('checkResetPasswordCode')->with('reset_code')->andReturn(false);

		$this->authentic->resetPassword($user, 'reset_code', 'foo_bah');

		$user->shouldNotHaveReceived('setPassword');
	}
}
