<?php

declare(strict_types=1);

namespace GeoSqueeze\Tests;

use PHPUnit\Framework\TestCase;
use GeoSqueeze\Geometry\Line;
use geoPHP\Geometry\Point;

final class LineTest extends TestCase
{
    /**
     * @dataProvider directionsProvider
     */
    public function testDirection(float $expect, Point $end): void
    {
        $start = new Point(0.0, 0.0);
        $line = new Line($start, $end);

        $this->assertEqualsWithDelta($expect, $line->direction, 1.0E-9);
    }

    public function testFromArray(): void
    {
        $line = Line::fromArray([
            [0.0, 0.0],
            [1.0, -1.0]
        ]);

        $this->assertInstanceOf(Line::class, $line);
        $this->assertEqualsWithDelta(135.0, $line->direction, 1.0E-9);
    }

    /**
     * @return array<string, array{float, Point}>
     */
    public function directionsProvider(): array
    {
        return [
            'north'     => [0.0,   new Point(0.0, 1.0)],
            'northeast' => [45.0,  new Point(1.0, 1.0)],
            'east'      => [90.0,  new Point(1.0, 0.0)],
            'southeast' => [135.0, new Point(1.0, -1.0)],
            'south'     => [180.0, new Point(0.0, -1.0)],
            'southwest' => [225.0, new Point(-1.0, -1.0)],
            'west'      => [270.0, new Point(-1.0, 0.0)],
            'northwest' => [315.0, new Point(-1.0, 1.0)],
        ];
    }
}
