<?php

declare(strict_types=1);

namespace GeoSqueeze\Tests;

use PHPUnit\Framework\TestCase;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use GeoSqueeze\GeoSqueeze;
use GeoSqueeze\Geometry\Line;
use GeoSqueeze\Geometry\Vertex;
use GeoSqueeze\Exception\LinesDoNotIntersect;

final class GeoSqueezeTest extends TestCase
{
    /**
     * @dataProvider compressionProvider
     */
    public function testCompression(int $expect, float $tolerance): void
    {
        $ogLine = $this->circleProvider();

        $geoSqueeze = new GeoSqueeze($tolerance);

        $compressed = $geoSqueeze->compressLineString($ogLine);
        $this->assertLessThanOrEqual($expect, $compressed->numGeometries());
    }

    /**
     * @return array<int, array{int, float}>
     */
    public function compressionProvider(): array
    {
        return [
            [60, 5.0],
            [40, 8.0],
        ];
    }

    public function circleProvider(): LineString
    {
        $sides = 360;

        $X = $Y = [];
        for ($i = 0; $i <= $sides; $i++) {
            $X[] = cos(2 * M_PI * $i / $sides);
            $Y[] = sin(2 * M_PI * $i / $sides);
        }

        return new LineString(array_map(fn($x, $y) => new Point($x, $y), $X, $Y));
    }
}
