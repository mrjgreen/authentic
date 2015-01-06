<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\PasswordHasher;

class PasswordHasherTest extends \PHPUnit_Framework_TestCase {

	public function testItHashesAndVerifiesPasswords()
	{
		$hasher = new PasswordHasher();

		$hash = $hasher->hash('foo_bar');

		$this->assertTrue($hasher->checkHash('foo_bar', $hash));

		$this->assertFalse($hasher->checkHash('baz_buzz', $hash));

		$this->assertFalse($hasher->needsRehash($hash));
	}
}
