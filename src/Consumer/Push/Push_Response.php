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
namespace Predis\Consumer\Push;

use ArrayAccess;
use Return_Type_Will_Change;
class Push_Response implements Push_Response_Interface, ArrayAccess
{
    /**
     * @var array
     */
    private $response;
    public function __construct(array $server_response)
    {
        $this->response = $server_response;
    }
    /**
     * {@inheritDoc}
     * @throws PushNotificationException
     */
    public function get_data_type(): string
    {
        if (!isset($this->response[0])) {
            throw new Push_Notification_Exception('Invalid server response');
        }
        return $this->response[0];
    }
    /**
     * {@inheritDoc}
     */
    public function get_payload(): array
    {
        return array_slice($this->response, 1);
    }
    public function offsetExists($offset): bool
    {
        return isset($this->response[$offset]);
    }
    #[Return_Type_Will_Change]
    public function offsetGet($offset)
    {
        return $this->response[$offset];
    }
    public function offsetSet($offset, $value): void
    {
        $this->response[$offset] = $value;
    }
    public function offsetUnset($offset): void
    {
        unset($this->response[$offset]);
    }
}