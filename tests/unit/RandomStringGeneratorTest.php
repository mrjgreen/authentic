<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\RandomStringGenerator;


class RandomStringGeneratorTest extends \PHPUnit_Framework_TestCase {

    public function testItGeneratesTwoRandomStrings()
    {
        $generator = new RandomStringGenerator();

        $rand1 = $generator->generate(5);

        $this->assertEquals(5, strlen($rand1));

        $this->assertNotEquals($rand1, $generator->generate(5));
    }

    public function testItGeneratesTwoLowSecurityRandomStrings()
    {
        $generator = new RandomStringGenerator(true);

        $rand1 = $generator->generate(5);

        $this->assertEquals(5, strlen($rand1));

        $this->assertNotEquals($rand1, $generator->generate(5));
    }
}
