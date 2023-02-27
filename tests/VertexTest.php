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
    public function testTurn(float $expect, Line $lineA): void
    {
        $lineB = $this->linePointingNorth();
        $vertex = new Vertex($lineA, $lineB);

        $this->assertFloat($expect, $vertex->turn());
    }

    /**
     * @dataProvider turnsProvider
     */
    public function testPriority(float $turn_angle, Line $lineA): void
    {
        $lineB = $this->linePointingNorth();
        $vertex = new Vertex($lineA, $lineB);

        // Priority is the inverse of the turn angle
        $priority = 360.0 - $turn_angle;

        $this->assertFloat($priority, $vertex->priority());
    }

    /**
     * @dataProvider turnsProvider
     */
    public function testSetLineA(float $turn_angle, Line $newLine): void
    {
        $lineA = $this->linePointingNorth(yTarget: 0.0);
        $lineB = $this->linePointingNorth(yTarget: 1.0);

        $vertex = new Vertex($lineA, $lineB);

        $vertex->setLineA($newLine);
        $this->assertFloat($turn_angle, $vertex->turn());
    }

    /**
     * @dataProvider turnsProvider
     */
    public function testSetLineB(float $turn_angle, Line $newLine): void
    {
        $targets = $newLine->start->asArray();
        $this->assertNotEmpty($targets);
        list($xTarget, $yTarget) = $targets;

        $lineA = $this->linePointingNorth($xTarget, $yTarget);
        $lineB = $this->linePointingNorth($xTarget, $yTarget + 1.0);

        $vertex = new Vertex($lineA, $lineB);

        $vertex->setLineB($newLine);
        $this->assertFloat($turn_angle, $vertex->turn());
    }

    public function testLinesDoNotIntersectException(): void
    {
        $this->expectException(LinesDoNotIntersect::class);
        $lineA = $this->linePointingNorth(yTarget: 1.0);
        $lineB = $this->linePointingNorth(yTarget: 0.0);
        $vertex = new Vertex($lineA, $lineB);
    }

    public function testPoint(): void
    {
        $lineA = $this->linePointingNorth(yTarget: 0.0);
        $lineB = $this->linePointingNorth(yTarget: 1.0);
        $vertex = new Vertex($lineA, $lineB);
        $this->assertSame($lineA->end, $vertex->point());
    }

    public function testDelete(): void
    {
        // test setup
        $coordinates = collect([[0, 0], [0, 1], [1, 2], [0, 3], [0, 4]]);

        // PHPStan is ignored here due to bug with callable(mixed $args)
        $points = $coordinates->mapSpread(fn(int $a, int $b) => new Point($a, $b)); //@phpstan-ignore-line
        $lines = $points->sliding(2)->mapSpread(fn(Point $a, Point $b) => new Line($a, $b)); //@phpstan-ignore-line
        $vertices = $lines->sliding(2)->mapSpread(fn(Line $a, Line $b) => new Vertex($a, $b)); //@phpstan-ignore-line

        // connecting vertices
        $vertices[0]->next = $vertices[1];
        $vertices[1]->next = $vertices[2];
        $vertices[1]->prev = $vertices[0];
        $vertices[2]->prev = $vertices[1];

        $vertices[1]->delete();

        // asserting vertices updated upon delete
        $this->assertSame($vertices[0]->next, $vertices[2]);
        $this->assertSame($vertices[2]->prev, $vertices[0]);

        $this->assertFloat(0.0, $vertices[0]->turn());
        $this->assertFloat(0.0, $vertices[2]->turn());
    }

    /**
     * @return array<string, array{float, Line}>
     */
    public function turnsProvider(): array
    {
        // the expected angle provided expects an initial line pointing northward
        return [
            'north'     => [0.0,   $this->lineDirection('north')],
            'northeast' => [45.0,  $this->lineDirection('northeast')],
            'east'      => [90.0,  $this->lineDirection('east')],
            'southeast' => [135.0, $this->lineDirection('southeast')],
            'south'     => [180.0, $this->lineDirection('south')],
            'southwest' => [225.0, $this->lineDirection('southwest')],
            'west'      => [270.0, $this->lineDirection('west')],
            'northwest' => [315.0, $this->lineDirection('northwest')],
        ];
    }

    private function assertFloat(float $expected, float $result): void
    {
        $delta = 1.0E-9;
        $this->assertEqualsWithDelta($expected, $result, $delta);
    }

    private function linePointingNorth(float $xTarget = 0.0, float $yTarget = 1.0): Line
    {
        return $this->lineDirection('north', $xTarget, $yTarget);
    }

    private function lineDirection(string $direction, float $xTarget = 0.0, float $yTarget = 0.0): Line
    {
        [$xDirection, $yDirection] = match ($direction) {
            'north'     => [0.0, 1.0],
            'northeast' => [1.0, 1.0],
            'east'      => [1.0, 0.0],
            'southeast' => [1.0, -1.0],
            'south'     => [0.0, -1.0],
            'southwest' => [-1.0, -1.0],
            'west'      => [-1.0, 0.0],
            'northwest' => [-1.0, 1.0],
            default     => [0, 0],
        };

        return $this->createLine($xTarget, $yTarget, $xDirection, $yDirection);
    }

    private function createLine(float $xTarget, float $yTarget, float $xDirection, float $yDirection): Line
    {
        return Line::fromArray([
            [$xTarget - $xDirection, $yTarget - $yDirection],
            [$xTarget, $yTarget],
        ]);
    }
}
