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
namespace Predis\Protocol\Parser;

use InvalidArgumentException;
use Predis\Protocol\Parser\Strategy\Parser_Strategy_Interface;
use Predis\Protocol\Parser\Strategy\Resp2Strategy;
use Predis\Protocol\Parser\Strategy\Resp3Strategy;
class Parser_Strategy_Resolver implements Parser_Strategy_Resolver_Interface
{
    /**
     * @var string[]
     */
    protected $protocol_strategy_mapping = [2 => Resp2Strategy::class, 3 => Resp3Strategy::class];
    /**
     * {@inheritDoc}
     */
    public function resolve(int $protocol_version): Parser_Strategy_Interface
    {
        if (!array_key_exists($protocol_version, $this->protocol_strategy_mapping)) {
            throw new InvalidArgumentException('Invalid protocol version given.');
        }
        $strategy = $this->protocol_strategy_mapping[$protocol_version];
        return new $strategy();
    }
}