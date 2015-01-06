<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\Authenticator;

class AuthenticatorTest extends \PHPUnit_Framework_TestCase {

	const RANDOM = 'thisiscompletelyrandom';

	protected $userProvider;

	protected $hasher;

	protected $session;

	protected $cookie;

	protected $random;

	/**
	 * @var Authenticator
	 */
	protected $authentic;

	/**
	 * Setup resources and dependencies.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->authentic = new Authenticator(
			$this->userProvider     = m::mock('Phroute\Authentic\User\UserRepositoryInterface'),
			$this->session          = m::mock('Phroute\Authentic\NamedPersistenceInterface'),
			$this->cookie           = m::mock('Phroute\Authentic\NamedPersistenceInterface'),
			$this->hasher    		= m::mock('Phroute\Authentic\PasswordHasher')
		);

		$this->random = m::mock('Phroute\Authentic\RandomStringGenerator');

		$this->authentic->setRandomStringGenerator($this->random);
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
		$credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'baz_bat',
		);

		$user = $this->getMock('Phroute\Authentic\User\UserInterface');

		$this->hasher->shouldReceive('checkHash')->andReturn(false);

		$this->userProvider->shouldReceive('findByLogin')->with($credentials['email'])->once()->andReturn($user);

		$this->authentic->authenticate($credentials);
	}

	public function testAuthenticatingUser()
	{
		$credentials = array(
			'email'    => 'foo@bar.com',
			'password' => 'baz_bat',
		);

		$user = $this->getUserMock();

		$user->shouldReceive('getPassword')->andReturn('hashed_pass');
		$this->hasher->shouldReceive('checkHash')->with($credentials['password'], 'hashed_pass')->andReturn(true);

		$this->hasher->shouldReceive('needsRehash')->with('hashed_pass')->andReturn(true);
		$this->hasher->shouldReceive('hash')->with('baz_bat')->andReturn('new_hashed_pass');

		$user->shouldReceive('setPassword')->once()->with('new_hashed_pass');

		$this->userProvider->shouldReceive('findByLogin')->with($credentials['email'])->once()->andReturn($user);

		$user->shouldReceive('setResetPasswordToken')->with(null)->once();

		$this->random->shouldReceive('generate')->andReturn(self::RANDOM);

		$user->shouldReceive('setRememberToken')->once();
		$user->shouldReceive('getId')->once()->andReturn('foo');
		$user->shouldReceive('onLogin')->once();

		$this->session->shouldReceive('set')->with(array('foo', self::RANDOM))->once();
		$this->cookie->shouldReceive('set')->with(json_encode(array('foo', self::RANDOM)))->once();

		$this->authentic->authenticate($credentials, true);
	}

	public function testCheckLoggingOut()
	{
		$this->authentic->setUser($this->getUserMock());
		$this->session->shouldReceive('get')->once();
		$this->session->shouldReceive('forget')->once();
		$this->cookie->shouldReceive('get')->once();
		$this->cookie->shouldReceive('forget')->once();

		$this->authentic->logout();
		$this->assertNull($this->authentic->getUser());
	}

	public function testCheckingUserWhenUserIsSet()
	{
		$user = $this->getUserMock();

		$this->authentic->setUser($user);
		$this->assertTrue($this->authentic->check());
	}


	public function testCheckingUserChecksSessionFirst()
	{
		$this->session->shouldReceive('get')->once()->andReturn(array('foo', 'persist_code'));
		$this->cookie->shouldReceive('get')->never();

		$this->userProvider->shouldReceive('findById')->andReturn($user = $this->getUserMock());

		$user->shouldReceive('getRememberToken')->once()->andReturn('persist_code');

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

		$this->userProvider->shouldReceive('findById')->andReturn($user = $this->getUserMock());

		$user->shouldReceive('getRememberToken')->once()->andReturn('persist_code_wrong');

		$this->assertFalse($this->authentic->check());
	}

	public function testCheckingUserChecksSessionFirstAndThenCookie()
	{
		$this->session->shouldReceive('get')->once();
		$this->cookie->shouldReceive('get')->once()->andReturn(json_encode(array('foo', 'persist_code')));

		$this->userProvider->shouldReceive('findById')->andReturn($user = $this->getUserMock());

		$user->shouldReceive('getRememberToken')->andReturn('persist_code');

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

		$user = $this->getUserMock();

		$this->hasher->shouldReceive('hash')
			->with($credentials['password'])
			->andReturn('abcdefg');

		$credentialsExpected['password'] = 'abcdefg';

		$this->userProvider->shouldReceive('registerUser')->with($credentialsExpected)->once()->andReturn($user);

		$this->assertEquals($user, $registeredUser = $this->authentic->register($credentials));
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
		$user = $this->getUserMock();

		$user->shouldReceive('getResetPasswordToken')->andReturn('reset_code');

		$user->shouldReceive('setPassword')->with('hashed_password');

		$this->hasher->shouldReceive('hash')->with('foo_bah')->andReturn('hashed_password');

		$this->assertTrue($this->authentic->resetPassword($user, 'reset_code', 'foo_bah'));
	}

	public function testResetPasswordForLogin()
	{
		$authentic = m::mock('Phroute\Authentic\Authenticator[resetPassword]', array(
			$this->userProvider,
			$this->session,
			$this->cookie,
			$this->hasher,
		));

		$this->userProvider->shouldReceive('findByLogin')->once()->with('test@foo')->andReturn($user = $this->getUserMock());

		$authentic->shouldReceive('resetPassword')->once()->with($user, 'foo', 'bar');
		$authentic->resetPasswordForLogin('test@foo', 'foo', 'bar');
	}

	public function testResetPasswordFailure()
	{
		$user = $this->getUserMock();

		$user->shouldReceive('getResetPasswordToken')->andReturn('wrong_reset_code');

		$this->authentic->resetPassword($user, 'reset_code', 'foo_bah');

		$user->shouldNotHaveReceived('setPassword');
	}

	public function testGenerateResetToken()
	{
		$this->random->shouldReceive('generate')->andReturn(self::RANDOM);

		$user = $this->getUserMock();

		$user->shouldReceive('setResetPasswordToken')->once()->with(self::RANDOM);

		$token = $this->authentic->generateResetToken($user);

		$this->assertEquals(self::RANDOM, $token);
	}


	public function testGenerateResetTokenForLogin()
	{
		$this->random->shouldReceive('generate')->andReturn(self::RANDOM);

		$user = $this->getUserMock();

		$this->userProvider->shouldReceive('findByLogin')->once()->with('foo@bar.com')->andReturn($user);

		$user->shouldReceive('setResetPasswordToken')->once()->with(self::RANDOM);

		$token = $this->authentic->generateResetTokenForLogin('foo@bar.com');

		$this->assertEquals(self::RANDOM, $token);
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\UserNotFoundException
	 */
	public function testGenerateResetTokenForNonExistentLogin()
	{
		$this->userProvider->shouldReceive('findByLogin')->andReturn(false);

		$this->authentic->generateResetTokenForLogin('foo@bar.com');
	}

	public function testSettingCredentialNames()
	{
		$this->authentic->setLoginCredentialName('login_test');
		$this->authentic->setPasswordCredentialName('password_test');
	}

	public function testItRefreshesAuthTokensNoRemember()
	{
		$this->cookie->shouldReceive('get')->andReturn(false);
		$this->cookie->shouldReceive('set')->never();
		$this->session->shouldReceive('set')->with(array('userId', self::RANDOM));

		$this->random->shouldReceive('generate')->andReturn(self::RANDOM);

		$user = $this->getUserMock();

		$user->shouldReceive('setRememberToken')->with(self::RANDOM);
		$user->shouldReceive('getId')->andReturn('userId');

		$this->authentic->setUser($user);

		$this->authentic->refreshAuthToken();
	}

	public function testItRefreshesAuthTokensWithRemember()
	{
		$this->cookie->shouldReceive('get')->andReturn(true);
		$this->cookie->shouldReceive('set')->once()->with(json_encode(array('userId', self::RANDOM)));
		$this->session->shouldReceive('set')->once()->with(array('userId', self::RANDOM));

		$this->random->shouldReceive('generate')->andReturn(self::RANDOM);

		$user = $this->getUserMock();

		$user->shouldReceive('setRememberToken')->with(self::RANDOM);
		$user->shouldReceive('getId')->andReturn('userId');

		$this->authentic->setUser($user);

		$this->authentic->refreshAuthToken();
	}

	/**
	 * @expectedException \Phroute\Authentic\Exception\AuthenticationException
	 */
	public function testItCannotRefreshNonLoggedInUser()
	{
		$this->session->shouldReceive('get')->andReturn(false);
		$this->cookie->shouldReceive('get')->andReturn(false);

		$this->authentic->refreshAuthToken();
	}

	/**
	 * @return \Phroute\Authentic\User\UserInterface
	 */
	private function getUserMock()
	{
		return m::mock('Phroute\Authentic\User\UserInterface');
	}
}
