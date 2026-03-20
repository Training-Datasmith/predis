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
namespace Predis\Consumer\Pub_Sub;

class Subscription_Context
{
    public const CONTEXT_SHARDED = 'sharded';
    public const CONTEXT_NON_SHARDED = 'non_sharded';
    /**
     * @var string
     */
    private $context;
    public function __construct(string $context = self::CONTEXT_NON_SHARDED)
    {
        $this->context = $context;
    }
    public function get_context(): string
    {
        return $this->context;
    }
}