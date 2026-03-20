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
namespace Predis\Transaction;

use Predis\Predis_Exception;
/**
 * Exception class that identifies a MULTI / EXEC transaction aborted by Redis.
 */
class Aborted_Multi_Exec_Exception extends Predis_Exception
{
    private $transaction;
    /**
     * @param MultiExec $transaction Transaction that generated the exception.
     * @param string    $message     Error message.
     * @param int       $code        Error code.
     */
    public function __construct(Multi_Exec $transaction, $message, $code = 0)
    {
        parent::__construct($message, is_null($code) ? 0 : $code);
        $this->transaction = $transaction;
    }
    /**
     * Returns the transaction that generated the exception.
     *
     * @return MultiExec
     */
    public function get_transaction()
    {
        return $this->transaction;
    }
}