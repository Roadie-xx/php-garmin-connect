<?php

declare(strict_types=1);

namespace Roadie;

use \DateTimeImmutable;
use Exception;

class Gpx
{
    const MEAN_EARTH_RADIUS = 6372.797;

    private array $points = [];

    private array $minMax = [
        'latitude' => [
            'min' => PHP_FLOAT_MAX,
            'max' => PHP_FLOAT_MIN,
        ],
        'longitude' => [
            'min' => PHP_FLOAT_MAX,
            'max' => PHP_FLOAT_MIN,
        ],
    ];

    private array $best = [
        1000 => ['duration' => PHP_INT_MAX],
        1609 => ['duration' => PHP_INT_MAX],
        5000 => ['duration' => PHP_INT_MAX],
        10000 => ['duration' => PHP_INT_MAX],
        16093 => ['duration' => PHP_INT_MAX],
        21100 => ['duration' => PHP_INT_MAX],
    ];

    /**
     * @throws Exception
     */
    public function loadFile(string $filename)
    {
        $index = 0;

        if (! file_exists($filename)) {
            throw new Exception('Can NOT open gpx-file');
        }

        $gpx = simplexml_load_file($filename);

        foreach ($gpx->trk->trkseg->trkpt as $point) {
            $extensions = $point->extensions->children("http://www.garmin.com/xmlschemas/TrackPointExtension/v1");

            if (isset($point->extensions, $extensions)) {
                $trackPointExtension = $extensions->children("http://www.garmin.com/xmlschemas/TrackPointExtension/v1");
            }

            $heartRate = isset($trackPointExtension, $trackPointExtension->hr) ? $trackPointExtension->hr : 0;
            $cadans = isset($trackPointExtension, $trackPointExtension->cad) ? $trackPointExtension->cad : 0;

            $this->points[$index] = [
                'latitude' => floatval((string) $point['lat']),
                'longitude' => floatval((string) $point['lon']),
                'elevation' => floatval((string) $point->ele),
                'time' => (new DateTimeImmutable((string) $point->time))->format('U'),
                'heartRate' => intval((string) $heartRate),
                'cadans' => intval((string) $cadans),
            ];

            $this->minMax = [
                'latitude' => [
                    'min' => min($this->minMax['latitude']['min'], $this->points[$index]['latitude']),
                    'max' => max($this->minMax['latitude']['max'], $this->points[$index]['latitude']),
                ],
                'longitude' => [
                    'min' => min($this->minMax['longitude']['min'], $this->points[$index]['longitude']),
                    'max' => max($this->minMax['longitude']['max'], $this->points[$index]['longitude']),
                ],
            ];

            $index++;
        }

        unset($gpx);
    }

    public function enrich()
    {
        foreach ($this->points as $index => $point) {
            if ($index === 0) {
                $this->points[$index]['distance'] = 0;
                $this->points[$index]['duration'] = 0;

                $this->points[$index]['totalDistance'] = 0;
                $this->points[$index]['totalDuration'] = 0;

                continue;
            }

            $this->points[$index]['distance'] = $this->distanceBetweenPoints($point, $this->points[$index - 1]);
            $this->points[$index]['duration'] = $point['time'] - $this->points[$index - 1]['time'];

            $this->points[$index]['totalDistance'] = $this->points[$index]['distance'] + $this->points[$index - 1]['totalDistance'];
            $this->points[$index]['totalDuration'] = $this->points[$index]['duration'] + $this->points[$index - 1]['totalDuration'];
        }
    }

    public function analyze()
    {
        // ini_set('memory_limit','10240M');
        // set_time_limit(5 * 60);

        $mustSplit = count($this->points) > 2000;

        foreach ($this->points as $endIndex => $endPoint) {
            // if ($mustSplit && $endIndex % 2 === 0) {
            //     continue;
            // }

            foreach ($this->points as $startIndex => $startPoint) {
                // if ($mustSplit && $startIndex % 2 === 0) {
                //     continue;
                // }

                if ($startIndex >= $endIndex) {
                    break;
                }

                $duration = $endPoint['totalDuration'] - $startPoint['totalDuration'];
                $distance = $endPoint['totalDistance'] - $startPoint['totalDistance'];

                foreach ($this->best as $key => $value) {
                    if ($distance > ($key / 1000) && $duration < $value['duration']) {
                        $this->best[$key] = [
                            'duration' => $duration,
                            'distance' => $distance,
                            'start' => $startIndex,
                            'end' => $endIndex,
                        ];
                    }
                }
            }
        }
    }

    public function getPoints(): array
    {
        return $this->points;
    }

    public function getBest(): array
    {
        return $this->best;
    }

    public function getMinMax(): array
    {
        return $this->minMax;
    }

    private function distanceBetweenPoints($pointA, $pointB): float
    {
        $pi180 = M_PI / 180;
        $latA = $pointA['latitude'] * $pi180;
        $lngA = $pointA['longitude'] * $pi180;
        $latB = $pointB['latitude'] * $pi180;
        $lngB = $pointB['longitude'] * $pi180;
        $diffLat = $latB - $latA;
        $diffLong = $lngB - $lngA;
        $calcA = sin($diffLat / 2) * sin($diffLat / 2) + cos($latA) * cos($latB) * sin($diffLong / 2) * sin($diffLong / 2);
        $calcB = 2 * atan2(sqrt($calcA), sqrt(1 - $calcA));

        return self::MEAN_EARTH_RADIUS * $calcB;
    }
}
