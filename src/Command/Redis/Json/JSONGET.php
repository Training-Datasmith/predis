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
namespace Predis\Command\Redis\Json;

use Predis\Command\Prefixable_Command as RedisCommand;
use Predis\Command\Traits\Json\Indent;
use Predis\Command\Traits\Json\Newline;
use Predis\Command\Traits\Json\Space;
/**
 * @see https://redis.io/commands/json.get/
 *
 * Return the value at path in JSON serialized form
 */
class JSONGET extends Redis_Command
{
    use Indent {
        Indent::setArguments as setIndent;
    }
    use Newline {
        Newline::setArguments as setNewline;
    }
    use Space {
        Space::setArguments as setSpace;
    }
    protected static $indent_argument_position_offset = 1;
    protected static $newline_argument_position_offset = 2;
    protected static $space_argument_position_offset = 3;
    public function get_id(): string
    {
        return 'JSON.GET';
    }
    public function set_arguments(array $arguments): void
    {
        $this->set_space($arguments);
        $arguments = $this->get_arguments();
        $this->set_newline($arguments);
        $arguments = $this->get_arguments();
        $this->set_indent($arguments);
        $this->filter_arguments();
    }
    public function prefix_keys($prefix): void
    {
        $this->apply_prefix_for_first_argument($prefix);
    }
}