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
namespace Predis\Protocol\Parser\Strategy;

use Predis\Protocol\Parser\Unexpected_Type_Exception;
use Predis\Response\Error;
use Predis\Response\Error_Interface;
use Predis\Response\Status as StatusResponse;
class Resp2Strategy implements Parser_Strategy_Interface
{
    public const TYPE_ARRAY = 'array';
    public const TYPE_BULK_STRING = 'bulkString';
    /**
     * Callbacks to process given RESP type.
     *
     * @var string[]
     */
    protected $type_callbacks = ['+' => 'parseSimpleString', '-' => 'parseError', ':' => 'parseInteger', '*' => 'parseArray', '$' => 'parseBulkString'];
    /**
     * RESP 2 Status responses.
     *
     * @var string[]
     */
    protected $status_response = ['OK', 'QUEUED', 'NOKEY', 'PONG'];
    /**
     * {@inheritDoc}
     */
    public function parse_data(string $data)
    {
        $type = $data[0];
        $payload = substr($data, 1, -2);
        if (!array_key_exists($type, $this->type_callbacks)) {
            throw new Unexpected_Type_Exception($type, 'Unexpected data type given.');
        }
        $callback = $this->type_callbacks[$type];
        return $this->{$callback}($payload);
    }
    /**
     * Parse simple string RESP type.
     *
     * @return StatusResponse|string
     */
    protected function parse_simple_string(string $string)
    {
        if (in_array($string, $this->status_response)) {
            return Status_Response::get($string);
        }
        return $string;
    }
    /**
     * Parse error RESP type.
     */
    protected function parse_error(string $string): Error_Interface
    {
        return new Error($string);
    }
    /**
     * Parse integer RESP type.
     */
    protected function parse_integer(string $string): int
    {
        return (int) $string;
    }
    /**
     * Parse array RESP type.
     *
     * @return array
     */
    protected function parse_array(string $string): ?array
    {
        $count = (int) $string;
        if ($count === -1) {
            return null;
        }
        return ['type' => self::TYPE_ARRAY, 'value' => $count];
    }
    /**
     * Parse bulk string RESP type.
     *
     * @return array
     */
    protected function parse_bulk_string(string $string): ?array
    {
        $size = (int) $string;
        if ($size === -1) {
            return null;
        }
        return ['type' => self::TYPE_BULK_STRING, 'value' => $size];
    }
}