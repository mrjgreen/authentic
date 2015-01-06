<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\NamedPersistence;


class NamesPersistenceTest extends \PHPUnit_Framework_TestCase {

	public function testItCallsSetAndGet()
	{
		$mock = m::mock('Phroute\Authentic\Persistence\PersistenceInterface');

		$mock->shouldReceive('set')->with('foo', 'bar');
		$mock->shouldReceive('forget')->with('foo');
		$mock->shouldReceive('get')->with('foo')->andReturn('buzz');

		$persistence = new NamedPersistence('foo', $mock);

		$persistence->set('bar');
		$persistence->forget('bar');
		$this->assertEquals('buzz', $persistence->get());
	}
}
