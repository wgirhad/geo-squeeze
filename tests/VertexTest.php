<?php

declare(strict_types=1);

namespace GeoSqueeze\Tests;

use PHPUnit\Framework\TestCase;
use geoPHP\Geometry\Point;
use GeoSqueeze\Geometry\Line;
use GeoSqueeze\Geometry\Vertex;
use GeoSqueeze\Exception\LinesDoNotIntersect;

final class VertexTest extends TestCase
{
    /**
     * @dataProvider turnsProvider
     */
    public function testTurn(float $expect, Line $lineB): void
    {
        $lineA = Line::fromArray([[0.0, -1.0],[0.0, 0.0]]);
        $vertex = new Vertex($lineA, $lineB);

        $this->assertEqualsWithDelta($expect, $vertex->turn(), 1.0E-9);
    }

    /**
     * @dataProvider turnsProvider
     */
    public function testPriority(float $expect_angle, Line $lineB): void
    {
        $lineA = Line::fromArray([[0.0, -1.0],[0.0, 0.0]]);
        $vertex = new Vertex($lineA, $lineB);
        $expect = 360.0 - $expect_angle;

        $this->assertEqualsWithDelta($expect, $vertex->priority(), 1.0E-9);
    }

    /**
     * @dataProvider setLineProvider
     */
    public function testSetLine(string $before, string $after, float $expectedAfterA): void
    {
        $data = $this->turnsProvider();

        $line = Line::fromArray([[0.0, -1.0],[0.0, 0.0]]);
        list($expectBefore, $lineBefore) = $data[$before];
        list($expectAfter, $lineAfter) = $data[$after];

        $vertex = new Vertex($line, $lineBefore);
        $this->assertEqualsWithDelta($expectBefore, $vertex->turn(), 1.0E-9);

        $vertex->setLineB($lineAfter);
        $this->assertEqualsWithDelta($expectAfter, $vertex->turn(), 1.0E-9);

        $vertex->setLineA(Line::fromArray([[0.0, 1.0],[0.0, 0.0]]));
        $this->assertEqualsWithDelta($expectedAfterA, $vertex->turn(), 1.0E-9);
    }

    public function testLinesDoNotIntersectException(): void
    {
        $this->expectException(LinesDoNotIntersect::class);
        $lineA = Line::fromArray([[0.0, -1.0], [0.0, 0.0]]);
        $lineB = Line::fromArray([[0.0, 0.0], [0.0, 1.0]]);
        $vertex = new Vertex($lineB, $lineA);
    }

    public function testPoint(): void
    {
        $lineA = Line::fromArray([[0.0, -1.0], [0.0, 0.0]]);
        $lineB = Line::fromArray([[0.0, 0.0], [0.0, 1.0]]);
        $vertex = new Vertex($lineA, $lineB);
        $this->assertSame($lineA->end, $vertex->point());
    }

    public function testDelete(): void
    {
        $points = [
            new Point(0, 0),
            new Point(0, 1),
            new Point(1, 2),
            new Point(0, 3),
            new Point(0, 4),
        ];

        $lines = [
            new Line($points[0], $points[1]),
            new Line($points[1], $points[2]),
            new Line($points[2], $points[3]),
            new Line($points[3], $points[4]),
        ];

        $vertices = [
            new Vertex($lines[0], $lines[1]),
            new Vertex($lines[1], $lines[2]),
            new Vertex($lines[2], $lines[3]),
        ];

        $vertices[0]->next = $vertices[1];
        $vertices[1]->next = $vertices[2];
        $vertices[1]->prev = $vertices[0];
        $vertices[2]->prev = $vertices[1];

        $this->assertEqualsWithDelta(45.0, $vertices[0]->turn(), 1.0E-9);
        $this->assertEqualsWithDelta(270.0, $vertices[1]->turn(), 1.0E-9);
        $this->assertEqualsWithDelta(315.0, $vertices[2]->turn(), 1.0E-9);

        $vertices[1]->delete();

        $this->assertSame($vertices[0]->next, $vertices[2]);
        $this->assertSame($vertices[2]->prev, $vertices[0]);

        $this->assertEqualsWithDelta(0.0, $vertices[0]->turn(), 1.0E-9);
        $this->assertEqualsWithDelta(0.0, $vertices[2]->turn(), 1.0E-9);
    }

    /**
     * @return array<int, array{string, string, float}>
     */
    public function setLineProvider(): array
    {
        return [
            ['north', 'northeast', 135.0],
            ['east', 'south', 0.0],
        ];
    }

    /**
     * @return array<string, array{float, Line}>
     */
    public function turnsProvider(): array
    {
        return [
            'north'     => [0.0,   Line::fromArray([[0.0, 0.0],[0.0, 1.0]])],
            'northeast' => [45.0,  Line::fromArray([[0.0, 0.0],[1.0, 1.0]])],
            'east'      => [90.0,  Line::fromArray([[0.0, 0.0],[1.0, 0.0]])],
            'southeast' => [135.0, Line::fromArray([[0.0, 0.0],[1.0, -1.0]])],
            'south'     => [180.0, Line::fromArray([[0.0, 0.0],[0.0, -1.0]])],
            'southwest' => [225.0, Line::fromArray([[0.0, 0.0],[-1.0, -1.0]])],
            'west'      => [270.0, Line::fromArray([[0.0, 0.0],[-1.0, 0.0]])],
            'northwest' => [315.0, Line::fromArray([[0.0, 0.0],[-1.0, 1.0]])],
        ];
    }
}
