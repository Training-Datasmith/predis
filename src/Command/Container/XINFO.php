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
namespace Predis\Command\Container;

use Predis\Command\Argument\Stream\X_Info_Stream_Options;
/**
 * @method array consumers(string $key, string $group)
 * @method array groups(string $key)
 * @method array stream(string $key, XInfoStreamOptions $options = null)
 */
class XINFO extends Abstract_Container
{
    public function get_container_command_id(): string
    {
        return 'XINFO';
    }
}