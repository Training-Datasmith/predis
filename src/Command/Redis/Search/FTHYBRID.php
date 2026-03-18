<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Search;

use Predis\Command\PrefixableCommand as RedisCommand;
use Predis\Command\Redis\Utils\CommandUtility;

class FTHYBRID extends RedisCommand
{
    public function getId(): string
    {
        return 'FT.HYBRID';
    }

    public function setArguments(array $arguments): void
    {
        [$index, $query] = $arguments;

        parent::setArguments(array_merge(
            [$index],
            $query->toArray()
        ));
    }

    /**
     * @return mixed[]
     */
    public function parseResponse($data): array
    {
        $response = CommandUtility::arrayToDictionary($data, null, false);

        foreach ($response['results'] as $key => $result) {
            $response['results'][$key] = CommandUtility::arrayToDictionary($result);
        }

        return $response;
    }

    public function prefixKeys($prefix): void
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
