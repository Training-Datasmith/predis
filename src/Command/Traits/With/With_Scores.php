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
namespace Predis\Command\Traits\With;

use Predis\Command\Command;
/**
 * Handles last argument passed into command as WITHSCORES.
 *
 * @mixin Command
 */
trait With_Scores
{
    public function set_arguments(array $arguments): void
    {
        $with_scores = array_pop($arguments);
        if (is_bool($with_scores) && $with_scores) {
            $arguments[] = 'WITHSCORES';
        } elseif (!is_bool($with_scores)) {
            $arguments[] = $with_scores;
        }
        parent::set_arguments($arguments);
    }
    /**
     * Checks for the presence of the WITHSCORES modifier.
     */
    private function is_with_score_modifier(): bool
    {
        $arguments = parent::get_arguments();
        $last_argument = !empty($arguments) ? $arguments[count($arguments) - 1] : null;
        return is_string($last_argument) && strtoupper($last_argument) === 'WITHSCORES';
    }
    public function parse_response(array $data): array
    {
        if ($this->is_with_score_modifier()) {
            $result = [];
            for ($i = 0, $i_max = count($data); $i < $i_max; ++$i) {
                if (is_array($data[$i])) {
                    $result[$data[$i][0]] = $data[$i][1];
                    // Relay
                } elseif (array_key_exists($i + 1, $data)) {
                    $result[$data[$i]] = $data[++$i];
                }
            }
            return $result;
        }
        return $data;
    }
}