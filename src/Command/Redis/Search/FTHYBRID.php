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
namespace Predis\Command\Redis\Search;

use Predis\Command\Prefixable_Command as RedisCommand;
use Predis\Command\Redis\Utils\Command_Utility;
class FTHYBRID extends Redis_Command
{
    public function get_id(): string
    {
        return 'FT.HYBRID';
    }
    public function set_arguments(array $arguments): void
    {
        [$index, $query] = $arguments;
        parent::set_arguments(array_merge([$index], $query->to_array()));
    }
    /**
     * @return mixed[]
     */
    public function parse_response($data): array
    {
        $response = Command_Utility::array_to_dictionary($data, null, false);
        foreach ($response['results'] as $key => $result) {
            $response['results'][$key] = Command_Utility::array_to_dictionary($result);
        }
        return $response;
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}