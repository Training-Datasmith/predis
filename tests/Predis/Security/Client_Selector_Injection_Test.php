<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Tests\Security;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\Connection\Parameters;

/**
 * Security tests verifying that the selector argument of Client::get_client_by()
 * is validated against an allowlist.
 *
 * Without allowlist validation an attacker-controlled selector string would be
 * interpolated into a method name via `"getConnectionBy{$selector}"`, enabling
 * arbitrary method invocation on the connection object (a form of SSRF or
 * information-disclosure depending on what methods are available).
 *
 * The fix restricts selectors to: id, key, slot, role, alias, command.
 */
class Client_Selector_Injection_Test extends TestCase
{
    /**
     * An unknown selector type must be rejected with an InvalidArgumentException
     * before any method is invoked on the connection object.
     */
    public function test_unknown_selector_is_rejected(): void
    {
        $client = new Client(new Parameters());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid selector type/i');

        $client->get_client_by('__toString', 'anything');
    }

    /**
     * A selector crafted to call a destructive method via interpolation must
     * be rejected (e.g. 'disconnect' would resolve to 'getConnectionByDisconnect').
     */
    public function test_crafted_selector_cannot_call_arbitrary_methods(): void
    {
        $client = new Client(new Parameters());

        $this->expectException(InvalidArgumentException::class);

        // 'disconnect' is not in the allowed list [id, key, slot, role, alias, command]
        $client->get_client_by('disconnect', 'value');
    }

    /**
     * A selector with path-traversal-like characters must be rejected.
     */
    public function test_selector_with_special_chars_is_rejected(): void
    {
        $client = new Client(new Parameters());

        $this->expectException(InvalidArgumentException::class);

        $client->get_client_by('../../etc', 'value');
    }

    /**
     * A null byte in the selector must be rejected.
     */
    public function test_selector_with_null_byte_is_rejected(): void
    {
        $client = new Client(new Parameters());

        $this->expectException(InvalidArgumentException::class);

        $client->get_client_by("id\x00injected", 'value');
    }

    /**
     * The valid selector values must not throw on the allowlist check itself.
     * (They may throw later if the connection doesn't implement the method —
     * that is expected and is a separate code path.)
     */
    public function test_valid_selectors_pass_allowlist_check(): void
    {
        $client = new Client(new Parameters());

        $validSelectors = ['id', 'key', 'slot', 'role', 'alias', 'command'];

        foreach ($validSelectors as $selector) {
            try {
                $client->get_client_by($selector, 'some-value');
                // If we get here, the connection returned something — that's fine
            } catch (InvalidArgumentException $e) {
                // Only acceptable if the message is about the selector NOT being supported
                // by this specific connection type — NOT about it being invalid
                $this->assertStringNotContainsStringIgnoringCase(
                    'Invalid selector type',
                    $e->getMessage(),
                    "Selector '{$selector}' should pass the allowlist check but got: " . $e->getMessage()
                );
            }
        }

        // If we reach here, the allowlist check did not reject valid selectors
        $this->assertTrue(true);
    }
}
