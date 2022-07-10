<?php

declare(strict_types=1);

namespace GeoSqueeze\Geometry;

use geoPHP\Geometry\Point;

class Line
{
    public readonly float $direction;
    public function __construct(
        public readonly Point $start,
        public readonly Point $end,
    ) {
        $this->direction = $this->calculateDirection();
    }

    private function calculateDirection(): float
    {
        $diff_x = $this->end->x() - $this->start->x();
        $diff_y = $this->end->y() - $this->start->y();
        $angle = atan2($diff_x, $diff_y) * 180 / M_PI;
        return floatval(($angle < 0) ? $angle + 360 : $angle);
    }

    /**
     * @param array<int, array<int, float>> $float_points
     */
    public static function fromArray(array $float_points): self
    {
        return new self(
            new Point($float_points[0][0], $float_points[0][1]),
            new Point($float_points[1][0], $float_points[1][1]),
        );
    }
}
