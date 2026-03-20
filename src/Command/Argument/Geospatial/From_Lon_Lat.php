<?php

declare (strict_types=1);
/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Predis\Command\Argument\Geospatial;

class From_Lon_Lat implements From_Interface
{
    private const KEYWORD = 'FROMLONLAT';
    /**
     * @var float
     */
    private $longitude;
    /**
     * @var float
     */
    private $latitude;
    public function __construct(float $longitude, float $latitude)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        return [self::KEYWORD, $this->longitude, $this->latitude];
    }
}