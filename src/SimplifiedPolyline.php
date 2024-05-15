<?php

declare(strict_types=1);

namespace Roadie;

use Roadie\Point2D;

/**
 * Based on https://github.com/david-r-edgar/RDP-PHP/blob/master/src/RDP.php
 */
class SimplifiedPolyline
{
    /**
     * Finds the perpendicular distance from a point to a straight line.
     */
    protected static function perpendicularDistance2d(Point2D $point, Point2D $lineStart, Point2D $lineEnd): float
    {
        if ($lineEnd->getX() === $lineStart->getX()) {
            // vertical lines: treat this case specially to avoid dividing by zero
            $result = abs($point->getX() - $lineEnd->getX());
        } else {
            $slope = (($lineEnd->getY() - $lineStart->getY()) / ($lineEnd->getX() - $lineStart->getX()));
            $passThroughY = (0 - $lineStart->getX()) * $slope + $lineStart->getY();
            $result = (abs(($slope * $point->getX()) - $point->getY() + $passThroughY)) / (sqrt($slope * $slope + 1));
        }

        return $result;
    }

    /**
     * RamerDouglasPeucker2d
     *
     * Reduces the number of points on a polyline by removing those that are closer to the line than the distance $epsilon.
     *
     * @param Point2D[] $points An array of Points2D.
     * @param float $epsilon The distance threshold to use. The unit should be the same as that of the coordinates of the points in $points.
     *
     * @return Point2D[] An array of Points2D. Each point returned in the result array will retain all its original data.
     *
     * @throws InvalidParameterException
     */
    public static function RamerDouglasPeucker2d(array $points, float $epsilon): array
    {
        if ($epsilon <= 0) {
            throw new InvalidParameterException('Non-positive epsilon.');
        }

        if (count($points) < 2) {
            return $points;
        }

        $maxDistance = 0;
        $index = 0;
        $totalPoints = count($points);

        // Find the point with the maximum distance
        for ($i = 1; $i < ($totalPoints - 1); $i++) {
            $distance = self::perpendicularDistance2d($points[$i], $points[0], $points[$totalPoints-1]);

            if ($distance > $maxDistance) {
                $index = $i;
                $maxDistance = $distance;
            }
        }

        // If max distance is greater than epsilon, recursively simplify
        if ($maxDistance >= $epsilon) {
            // Recursive call on each 'half' of the polyline
            $recursiveResults1 = self::RamerDouglasPeucker2d(
                array_slice($points, 0, $index + 1),
                $epsilon
            );
            $recursiveResults2 = self::RamerDouglasPeucker2d(
                array_slice($points, $index, $totalPoints - $index),
                $epsilon);

            // Build the result list
            $resulPoints = array_merge(
                array_slice($recursiveResults1, 0, count($recursiveResults1) - 1),
                array_slice($recursiveResults2, 0, count($recursiveResults2))
            );
        } else {
            $resulPoints = [$points[0], $points[$totalPoints-1]];
        }

        return $resulPoints;
    }
}
