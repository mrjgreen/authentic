<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\Persistence\CookieProxy;
use Phroute\Authentic\Persistence\NativeSession;
use Symfony\Component\HttpFoundation\Cookie;

// We need to mock the time function - use namespacing to achieve this
include __DIR__ . '/timeFunction.php';

class PersistenceTest extends \PHPUnit_Framework_TestCase {

	public function testPersistenceSession()
	{
		$persistence = new NativeSession(array());

		$persistence->set('bar', 'foo');

		$this->assertEquals('foo', $persistence->get('bar'));

		$persistence->forget('bar');

		$this->assertNull($persistence->get('bar'));
	}

	public function testPersistenceCookieProxy()
	{
		$persistence = new CookieProxy(array('bar' => 'foo'));

		$this->assertEquals('foo', $persistence->get('bar'));

		$persistence->forget('bar');

		// Cookies remember :)
		$this->assertEquals('foo', $persistence->get('bar'));

		$persistence->set('foo', 'bar');

		$queued = $persistence->getQueuedCookies();

		$this->assertCount(2, $queued);

		$this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $queued[0]);
		$this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $queued[1]);

		$this->assertEquals('bar', $queued[0]->getName());
		$this->assertNull($queued[0]->getValue());
		$this->assertTrue($queued[0]->isCleared());

		$this->assertEquals('foo', $queued[1]->getName());
		$this->assertEquals('bar',$queued[1]->getValue());
		$this->assertFalse($queued[1]->isCleared());
	}
}
