<?php

namespace GeoSqueeze\Geometry;

use geoPHP\Geometry\Point;
use GeoSqueeze\Exception;

class Vertex
{
    private float $turn_angle;
    private Point $reference;
    public ?Vertex $prev = null;
    public ?Vertex $next = null;
    private Line $lineA;
    private Line $lineB;

    public function __construct(Line $lineA, Line $lineB)
    {
        $this->reference = $lineA->end;
        $this->setLineA($lineA);
        $this->setLineB($lineB);
    }

    public function setLineA(Line $line): void
    {
        if (!$line->end->equals($this->point())) {
            throw new Exception\LinesDoNotIntersect();
        }

        $this->lineA = $line;

        if (isset($this->lineB)) {
            $this->calculateTurn();
        }
    }

    public function setLineB(Line $line): void
    {
        if (!$line->start->equals($this->point())) {
            throw new Exception\LinesDoNotIntersect();
        }

        $this->lineB = $line;

        if (isset($this->lineA)) {
            $this->calculateTurn();
        }
    }

    private function calculateTurn(): void
    {
        $this->turn_angle = abs($this->lineA->direction - $this->lineB->direction);
    }

    public function turn(): float
    {
        return $this->turn_angle;
    }

    public function point(): Point
    {
        return $this->reference;
    }

    public function priority(): float
    {
        return 360.0 - $this->turn_angle;
    }

    public function delete(): void
    {
        $newLine = new Line(
            $this->lineA->start,
            $this->lineB->end
        );

        if (isset($this->prev)) {
            $this->prev->setLineB($newLine);
            $this->prev->next = $this->next;
        }

        if (isset($this->next)) {
            $this->next->setLineA($newLine);
            $this->next->prev = $this->prev;
        }
    }
}
