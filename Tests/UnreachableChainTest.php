<?php

namespace Omniticket\Tests;

use Omniticket\Exception\UnreachableException;
use Omniticket\Http\JsonApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * Nothing of the key anywhere in what a log of a failed call writes: the messages, the previous
 * exception's (a copy of the HTTP client's, its class and code kept, the key's parameter masked),
 * and the traces' arguments, which PHP keeps unless zend.exception_ignore_args is on.
 */
final class UnreachableChainTest extends TestCase
{
    public function testNoPartOfAnUnreachableChainHoldsTheKey(): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        $http = new MockHttpClient(static function (string $method, string $url): never {
            throw new TransportException(\sprintf('Could not resolve host: api.example for "%s".', $url), 6);
        });

        try {
            (new JsonApi($http, 'Example'))->request('GET', 'https://api.example/v1/events', ['query' => ['apikey' => 'secret-api-key-123', 'page' => 2], 'auth_bearer' => 'bearer-secret-456'], 'the events');
            self::fail('no exception');
        } catch (UnreachableException $e) {
            $previous = $e->getPrevious();
            self::assertInstanceOf(TransportException::class, $previous);
            self::assertSame(6, $previous->getCode());
            self::assertStringContainsString('apikey=***', $previous->getMessage());
            self::assertStringContainsString('apikey=***', $e->getMessage());
            self::assertNotEmpty($e->getTrace());
            for ($one = $e; null !== $one; $one = $one->getPrevious()) {
                foreach (['secret-api-key-123', 'bearer-secret-456'] as $secret) {
                    self::assertStringNotContainsString($secret, $one->getMessage());
                    self::assertStringNotContainsString($secret, print_r($one->getTrace(), true));
                    self::assertStringNotContainsString($secret, $one->getTraceAsString());
                }
            }
        } finally {
            ini_set('zend.exception_ignore_args', false !== $ignoreArgs ? $ignoreArgs : '1');
        }
    }

    /**
     * An error answered - the key refused, nothing there, a server error, a body that is not JSON:
     * the exception thrown where the request's options stood keeps no argument in its trace, so a
     * log of it (zend.exception_ignore_args off) holds no key.
     */
    public function testAnErrorAnswerKeepsNoKeyInItsTrace(): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        try {
            foreach ([[401, '{"message": "no"}'], [404, '{}'], [500, '{"message": "down"}'], [200, 'plain text']] as [$status, $body]) {
                $http = new \Symfony\Component\HttpClient\MockHttpClient(new \Symfony\Component\HttpClient\Response\MockResponse($body, ['http_code' => $status]));
                try {
                    (new JsonApi($http, 'Example'))->request('GET', 'https://api.example/v1/events', ['query' => ['apikey' => 'error-key-1234567'], 'auth_bearer' => 'error-token-1234567'], 'the events');
                    if (404 === $status) {
                        continue;
                    }
                    self::fail("no exception on $status");
                } catch (\Omniticket\Exception\OmniticketException $e) {
                    self::assertNotEmpty($e->getTrace());
                    foreach (['error-key-1234567', 'error-token-1234567'] as $secret) {
                        self::assertFalse(str_contains($e->getMessage(), $secret), $e->getMessage());
                        self::assertFalse(str_contains(print_r($e->getTrace(), true), $secret), "$status: the trace holds $secret");
                    }
                }
            }
        } finally {
            ini_set('zend.exception_ignore_args', false !== $ignoreArgs ? $ignoreArgs : '1');
        }
    }
}
