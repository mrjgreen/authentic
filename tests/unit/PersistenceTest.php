<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\Persistence\CookieProxy;
use Phroute\Authentic\Persistence\NativeSession;

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

		$queue = array(
			array('bar', null, 100 -(60 * 60 * 24 * 365 * 10)),
			array('foo', 'bar', 100 + (60 * 60 * 24 * 365 * 10)),
		);

		$this->assertEquals($queue, $persistence->getQueuedCookies());
	}
}
