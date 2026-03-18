<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class VEMB extends RedisCommand
{
    /**
     * @var bool
     */
    private $isRaw = false;

    public function getId(): string
    {
        return 'VEMB';
    }

    public function setArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0], $arguments[1]];

        if (isset($arguments[2])) {
            $this->isRaw = true;
            $processedArguments[] = 'RAW';
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param                            $data
     * @return array|float[]|string|null
     */
    public function parseResponse($data): array
    {
        if (!$this->isRaw) {
            return array_map(static function ($value): float {
                return (float) $value;
            }, $data);
        }

        $parsedData = [];

        for ($i = 0; $i < count($data); $i++) {
            if ($i > 1) {
                $parsedData[] = (float) $data[$i];
            } else {
                $parsedData[] = $data[$i];
            }
        }

        return $parsedData;
    }
}
