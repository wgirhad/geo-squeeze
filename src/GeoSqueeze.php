<?php

namespace GeoSqueeze;

use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use GeoSqueeze\Geometry\Line;
use GeoSqueeze\Geometry\Vertex;
use SplPriorityQueue;
use SplObjectStorage;

class GeoSqueeze
{
    public function __construct(
        public readonly float $tolerance = 8.0,
        public readonly int $precision = 6,
    ) {
    }

    public function compressLineString(LineString $lineString): LineString
    {
        if ($lineString->numGeometries() < 3) {
            return $lineString;
        }

        $points = $lineString->getComponents();
        $points = $this->reducePrecision($points);
        $vertices = $this->assembleVertices($points);
        $points = $this->assemblePointsStorage($points);
        $this->filterVertices($points, $vertices);

        return new LineString(iterator_to_array($points, false));
    }

    /**
     * @param array<int, Point> $points
     * @return array<int, Point>
     */
    protected function reducePrecision(array $points): array
    {
        return array_map(fn(Point $point) => new Point(
            round($point->x(), $this->precision),
            round($point->y(), $this->precision),
        ), $points);
    }

    /**
     * @param SplObjectStorage<Point, null> $points
     * @param SplPriorityQueue<float, Vertex> $vertices
     */
    protected function filterVertices(SplObjectStorage $points, SplPriorityQueue $vertices): void
    {
        while ($vertices->valid()) {
            /** @var array{priority: float, data: Vertex} $extraction */
            $extraction = $vertices->extract();

            $vertex = $extraction['data'];
            $priority = $extraction['priority'];

            if ($priority != $vertex->priority()) {
                $vertices->insert($vertex, $vertex->priority());
                continue;
            }

            if ($vertex->turn() > $this->tolerance) {
                break;
            }

            $vertex->delete();
            $points->detach($vertex->point());
        }
    }

    /**
     * @param array<int, Point> $points
     * @return SplObjectStorage<Point, null>
     */
    private function assemblePointsStorage(array $points): SplObjectStorage
    {
        /** @var SplObjectStorage<Point, null> $storage */
        $storage = new SplObjectStorage();
        array_map($storage->attach(...), $points);
        return $storage;
    }

    /**
     * @param array<int, Point> $points
     * @return SplPriorityQueue<float, Vertex>
     */
    private function assembleVertices(array $points): SplPriorityQueue
    {
        /** @var SplPriorityQueue<float, Vertex> $vertices */
        $vertices = new SplPriorityQueue();
        $vertices->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

        $prev_line = null;
        $prev_vertex = null;
        $last = count($points) - 2;

        foreach ($points as $i => $point) {
            if (is_null($prev_line)) {
                $prev_line = new Line($points[0], $points[1]);
                continue;
            }

            if ($i >= $last) {
                break;
            }

            $curr_line = new Line($point, $points[$i + 1]);
            $vertex = new Vertex($prev_line, $curr_line);
            $prev_line = $curr_line;
            $vertices->insert($vertex, $vertex->priority());

            if (!is_null($prev_vertex)) {
                $vertex->prev = $prev_vertex;
                $prev_vertex->next = $vertex;
            }

            $prev_vertex = $vertex;
        }

        return $vertices;
    }
}
