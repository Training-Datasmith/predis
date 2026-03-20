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
namespace Predis\Session;

use Predis\Client_Interface;
use Return_Type_Will_Change;
use Session_Handler_Interface;
/**
 * Session handler class that relies on Predis\Client to store PHP's sessions
 * data into one or multiple Redis servers.
 *
 * This class is mostly intended for PHP 5.4 but it can be used under PHP 5.3
 * provided that a polyfill for `SessionHandlerInterface` is defined by either
 * you or an external package such as `symfony/http-foundation`.
 */
class Handler implements Session_Handler_Interface
{
    protected $client;
    protected $ttl;
    /**
     * @param ClientInterface $client  Fully initialized client instance.
     * @param array           $options Session handler options.
     */
    public function __construct(Client_Interface $client, array $options = [])
    {
        $this->client = $client;
        if (isset($options['gc_maxlifetime'])) {
            $this->ttl = (int) $options['gc_maxlifetime'];
        } else {
            $this->ttl = max(1440, (int) ini_get('session.gc_maxlifetime'));
        }
    }
    /**
     * Registers this instance as the current session handler.
     */
    public function register(): void
    {
        session_set_save_handler($this, true);
    }
    /**
     * @param  string $save_path
     * @param  string $session_id
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function open($save_path, $session_id)
    {
        // NOOP
        return true;
    }
    /**
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function close()
    {
        // NOOP
        return true;
    }
    /**
     * @param  int  $maxlifetime
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function gc($maxlifetime)
    {
        // NOOP
        return true;
    }
    /**
     * @param  string $session_id
     * @return string
     */
    #[Return_Type_Will_Change]
    public function read($session_id)
    {
        if ($data = $this->client->get($session_id)) {
            return $data;
        }
        return '';
    }
    /**
     * @param  string $session_id
     * @param  string $session_data
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function write($session_id, $session_data)
    {
        $this->client->setex($session_id, $this->ttl, $session_data);
        return true;
    }
    /**
     * @param  string $session_id
     * @return bool
     */
    #[Return_Type_Will_Change]
    public function destroy($session_id)
    {
        $this->client->del($session_id);
        return true;
    }
    /**
     * Returns the underlying client instance.
     *
     * @return ClientInterface
     */
    public function get_client()
    {
        return $this->client;
    }
    /**
     * Returns the session max lifetime value.
     *
     * @return int
     */
    public function get_max_life_time()
    {
        return $this->ttl;
    }
}