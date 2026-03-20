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
namespace Predis\Command\Argument\Server;

use Predis\Command\Argument\Arrayable_Argument;
class To implements Arrayable_Argument
{
    private const KEYWORD = 'TO';
    private const FORCE_KEYWORD = 'FORCE';
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var bool
     */
    private $is_force;
    public function __construct(string $host, int $port, bool $is_force = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->is_force = $is_force;
    }
    /**
     * {@inheritDoc}
     */
    public function to_array(): array
    {
        $arguments = [self::KEYWORD, $this->host, $this->port];
        if ($this->is_force) {
            $arguments[] = self::FORCE_KEYWORD;
        }
        return $arguments;
    }
}