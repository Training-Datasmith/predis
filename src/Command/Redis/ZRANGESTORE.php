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
namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\By\By_Lex_By_Score;
use Predis\Command\Traits\Limit\Limit;
use Predis\Command\Traits\Rev;
/**
 * @see https://redis.io/commands/zrangestore/
 *
 * This command is like ZRANGE, but stores the result in the destination key.
 */
class ZRANGESTORE extends Redis_Command
{
    use By_Lex_By_Score {
        By_Lex_By_Score::setArguments as setByLexByScoreArgument;
    }
    use Rev {
        Rev::setArguments as setReversedArgument;
    }
    use Limit {
        Limit::setArguments as setLimitArguments;
    }
    protected static $by_lex_by_score_argument_position_offset = 4;
    protected static $rev_argument_position_offset = 5;
    protected static $limit_argument_position_offset = 6;
    public function get_id(): string
    {
        return 'ZRANGESTORE';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_by_lex_by_score_argument($arguments);
        $arguments = $this->get_arguments();
        $this->set_reversed_argument($arguments);
        $arguments = $this->get_arguments();
        $this->set_limit_arguments($arguments);
        $this->filter_arguments();
    }
}