<?php

namespace Omniticket\Http;

use Omnisense\Contracts\Exception\UnreachableException as Unreached;
use Omnisense\Contracts\Http\JsonClient;
use Omnisense\Contracts\Policy\Backoff;
use Omniticket\Exception\InvalidKeyException;
use Omniticket\Exception\NotFoundException;
use Omniticket\Exception\ProviderException;
use Omniticket\Exception\UnreachableException;
use Omniticket\Support\Data;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A JSON API called through the application's HTTP client (Symfony's
 * contracts: its own client, or a MockHttpClient in a test), the answer
 * turned into Data and every failure into one of omniticket's exceptions:
 * not reached (UnreachableException), the key refused (401, 403:
 * InvalidKeyException), nothing there (404: NotFoundException), any other
 * error or an answer that is not JSON (ProviderException) - with the
 * provider's own message when it gives one.
 *
 * The exchange is omnisense/contracts' JsonClient (raw(): every status
 * given back, read here): given the provider's Backoff (Config::backoff()
 * in a factory's build()), it waits while the provider asked to and reads
 * every response's Retry-After and RateLimit fields - a 429 is then a
 * UsageLimitReachedException with the wait asked, and the next calls are
 * held until it is over. Without one, a 429 is a ProviderException as any
 * other error.
 */
final class JsonApi
{
    private readonly JsonClient $client;

    public function __construct(HttpClientInterface $http, private readonly string $provider, ?Backoff $backoff = null)
    {
        $this->client = new JsonClient($http, $provider, [], $backoff);
    }

    /** @param array<string, mixed> $options the HTTP client's options (query, headers, json, body, auth_bearer...) */
    public function request(string $method, string $url, array $options = [], string $what = 'the request'): Data
    {
        $headers = \is_array($options['headers'] ?? null) ? self::headers($options['headers']) : [];
        unset($options['headers']);
        try {
            $answer = $this->client->raw($method, $url, $options, $headers, range(400, 599));
        } catch (Unreached $e) {
            // The kernel's copy of the client's exception, the request's secrets masked; no trace keeping the options.
            $previous = $e->getPrevious() ?? $e;

            throw Redaction::withoutArguments(new UnreachableException(\sprintf('%s could not be reached for %s: %s', $this->provider, $what, $previous->getMessage()), 0, $previous));
        }
        $status = $answer->status;
        $decoded = json_decode($answer->body, true);
        $message = self::message(Data::of($decoded));
        if (401 === $status || 403 === $status) {
            throw Redaction::withoutArguments(InvalidKeyException::refused($this->provider, $what, $status, $message));
        }
        if (404 === $status) {
            throw Redaction::withoutArguments(NotFoundException::of($this->provider, $what, $message));
        }
        if ($status >= 400) {
            throw Redaction::withoutArguments(ProviderException::answered($this->provider, $what, $status, $message));
        }
        if (!\is_array($decoded)) {
            throw Redaction::withoutArguments(ProviderException::answered($this->provider, $what, $status, 'an answer that is not JSON'));
        }

        return new Data($decoded);
    }

    /** The error message an answer carries, under the names APIs give it. */
    public static function message(Data $answer): ?string
    {
        foreach (['error_description', 'error.message', 'message', 'detail', 'errors.0.message', 'errors.0.detail', 'fault.faultstring', 'error'] as $path) {
            if (null !== ($message = $answer->string($path))) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $headers
     *
     * @return array<string, string|list<string>>
     */
    private static function headers(array $headers): array
    {
        $named = [];
        foreach ($headers as $name => $value) {
            if (\is_int($name) && \is_string($value) && str_contains($value, ':')) {
                [$name, $value] = array_map('trim', explode(':', $value, 2));
            }
            if (\is_string($name) && (\is_scalar($value) || \is_array($value))) {
                $named[$name] = \is_array($value) ? array_values(array_map(static fn ($one) => \is_scalar($one) ? (string) $one : '', $value)) : (string) $value;
            }
        }

        return $named;
    }
}
