<?php

declare(strict_types=1);

namespace Tests\Roadie;

use Roadie\InvalidParameterException;
use Roadie\Point2D;
use Roadie\RamerDouglasPeucker as RDP;
use PHPUnit\Framework\TestCase;

class RamerDouglasPeuckerTest extends TestCase
{
    // Basic tests

    /**
     * @throws InvalidParameterException
     */
    public function testBasic1()
    {
        $line = [
            new Point2D(150, 10),
            new Point2D(200, 100),
            new Point2D(360, 170),
            new Point2D(500, 280),
        ];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 30);

        $expectedResult = [
            new Point2D(150, 10),
            new Point2D(200, 100),
            new Point2D(500, 280),
    ]   ;

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testBasic2()
    {
        $line = [
            new Point2D(-30, -40),
            new Point2D(-20, -10),
            new Point2D(10, 10),
            new Point2D(50, 0),
            new Point2D(40, -30),
            new Point2D(10, -40),
        ];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 12);

        $expectedResult = [
            new Point2D(-30, -40),
            new Point2D(10, 10),
            new Point2D(50, 0),
            new Point2D(40, -30),
            new Point2D(10, -40),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 15);

        $expectedResult = [
            new Point2D(-30, -40),
            new Point2D(10, 10),
            new Point2D(50, 0),
            new Point2D(10, -40),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 20);

        $expectedResult = [
            new Point2D(-30, -40),
            new Point2D(10, 10),
            new Point2D(50, 0),
            new Point2D(10, -40),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 45);

        $expectedResult = [
            new Point2D(-30, -40),
            new Point2D(10, 10),
            new Point2D(10, -40),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testBasic3()
    {
        $line = [
            new Point2D(0.0034, 0.013),
            new Point2D(0.0048, 0.006),
            new Point2D(0.0062, 0.01),
            new Point2D(0.0087, 0.009),
        ];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 0.001);

        $expectedResult = [
            new Point2D(0.0034, 0.013),
            new Point2D(0.0048, 0.006),
            new Point2D(0.0062, 0.01),
            new Point2D(0.0087, 0.009),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 0.003);

        $expectedResult = [
            new Point2D(0.0034, 0.013),
            new Point2D(0.0048, 0.006),
            new Point2D(0.0087, 0.009),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 0.01);

        $expectedResult = [
            new Point2D(0.0034, 0.013),
            new Point2D(0.0087, 0.009),
        ];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    // Edge Cases

    /**
     * @throws InvalidParameterException
     */
    public function testNoPointsInLine()
    {
        $line = [];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 1);

        $expectedResult = [];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testOnePointInLine()
    {
        $line = [new Point2D(10, 10)];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 1);

        $expectedResult = [new Point2D(10, 10)];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    public function testTwoPointsInLine()
    {
        $line = [new Point2D(10, 10), new Point2D(20, 20)];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 1);

        $expectedResult = [new Point2D(10, 10), new Point2D(20, 20)];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testLineWithJustTwoIdenticalPoints()
    {
        $line = [new Point2D(1, 2), new Point2D(3, 5)];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 1);

        $expectedResult = [new Point2D(1, 2), new Point2D(3, 5)];;

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 10);
        //same expected result
        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testThreePointsWithIdenticalStartAndEndLine()
    {
        $line = [new Point2D(0.1, 0.1), new Point2D(0.9, 0.7), new Point2D(0.1, 0.1)];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 0.2);

        $expectedResult = [new Point2D(0.1, 0.1), new Point2D(0.9, 0.7), new Point2D(0.1, 0.1)];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 0.9);

        $expectedResult = [new Point2D(0.1, 0.1), new Point2D(0.1, 0.1)];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    public function testEpsilon0()
    {
        $line = [new Point2D(3400, 89000), new Point2D(5500, 52000), new Point2D(4800, 41000)];

        $invalidParameterExceptionsCaught = 0;

        try {
            $rdpResult = RDP::RamerDouglasPeucker2d($line, 0);
        } catch (InvalidParameterException $e) {
            $invalidParameterExceptionsCaught ++;
        }

        $this->assertEquals(1, $invalidParameterExceptionsCaught, "expected exception not thrown");
    }

    public function testNegativeEpsilon()
    {
        $line = [new Point2D(125.6, 89.5), new Point2D(97.4, 101.0), new Point2D(70.8, 109.1)];

        $invalidParameterExceptionsCaught = 0;

        try {
            $rdpResult = RDP::RamerDouglasPeucker2d($line, -20);
        } catch (InvalidParameterException $e) {
            $invalidParameterExceptionsCaught ++;
        }

        $this->assertEquals(1, $invalidParameterExceptionsCaught, "expected exception not thrown");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testHorizontalLine()
    {
        $line = [new Point2D(10, 10), new Point2D(20, 10), new Point2D(30, 10), new Point2D(40, 10)];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 5);

        $expectedResult = [new Point2D(10, 10), new Point2D(40, 10)];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }

    /**
     * @throws InvalidParameterException
     */
    public function testVerticalLine()
    {
        $line = [new Point2D(-20, -20), new Point2D(-20, -10), new Point2D(-20, 0), new Point2D(-20, 10)];

        $rdpResult = RDP::RamerDouglasPeucker2d($line, 5);

        $expectedResult = [new Point2D(-20, -20), new Point2D(-20, 10)];

        $this->assertEquals($expectedResult, $rdpResult, "result polyline array incorrect");
    }
}