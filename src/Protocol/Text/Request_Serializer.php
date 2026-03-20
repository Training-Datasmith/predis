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
namespace Predis\Protocol\Text;

use Predis\Command\Command_Interface;
use Predis\Protocol\Request_Serializer_Interface;
/**
 * Request serializer for the standard Redis wire protocol.
 *
 * @see http://redis.io/topics/protocol
 */
class Request_Serializer implements Request_Serializer_Interface
{
    /**
     * {@inheritdoc}
     */
    public function serialize(Command_Interface $command): string
    {
        $command_id = $command->get_id();
        $arguments = $command->get_arguments();
        $cmdlen = strlen($command_id);
        $reqlen = count($arguments) + 1;
        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$command_id}\r\n";
        foreach ($arguments as $argument) {
            $arglen = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }
        return $buffer;
    }
}