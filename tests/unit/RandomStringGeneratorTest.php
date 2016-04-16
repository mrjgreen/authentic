<?php namespace Phroute\Authentic\Tests;

use Mockery as m;
use Phroute\Authentic\RandomStringGenerator;


class RandomStringGeneratorTest extends \PHPUnit_Framework_TestCase {

    public function testItGeneratesTwoRandomStrings()
    {
        $generator = new RandomStringGenerator();

        for($i = 1; $i < 50; $i++)
        {
            $rand1 = $generator->generate($i);

            $this->assertEquals($i, strlen($rand1));

            $this->assertNotEquals($rand1, $generator->generate($i));
        }
    }
}
