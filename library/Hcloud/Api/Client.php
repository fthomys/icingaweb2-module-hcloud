<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Api;

use Generator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class Client
{
    public const CLOUD_BASE_URI = 'https://api.hetzner.cloud/v1/';
    public const STORAGE_BASE_URI = 'https://api.hetzner.com/v1/';

    public const MAX_PER_PAGE = 50;

    private const RATE_LIMIT_FLOOR = 3;
    private const MAX_SLEEP_SECONDS = 90;

    private GuzzleClient $http;

    private int $requestCount = 0;

    private ?int $rateLimitRemaining = null;

    private ?int $rateLimitReset = null;

    /** @var callable(int): void */
    private $sleeper;

    public function __construct(
        private readonly string $token,
        private readonly string $baseUri = self::CLOUD_BASE_URI,
        ?HandlerStack $handler = null,
        private readonly int $perPage = self::MAX_PER_PAGE,
        private readonly int $maxRetries = 3,
        ?callable $sleeper = null
    ) {
        $this->sleeper = $sleeper ?? static function (int $seconds): void {
            sleep($seconds);
        };

        $options = [
            'base_uri' => $this->baseUri,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'User-Agent' => 'icingaweb2-module-hcloud',
            ],
        ];

        if ($handler !== null) {
            $options['handler'] = $handler;
        }

        $this->http = new GuzzleClient($options);
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->decode($this->request($path, $query));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function getPaginated(string $path, ?string $key = null, array $query = []): Generator
    {
        $key ??= self::collectionKey($path);
        $page = 1;

        while (true) {
            $payload = $this->get($path, $query + ['page' => $page, 'per_page' => $this->perPage]);

            $entries = $payload[$key] ?? null;
            if (! is_array($entries)) {
                throw new RuntimeException(sprintf('Response for %s has no "%s" collection.', $path, $key));
            }

            foreach ($entries as $entry) {
                if (is_array($entry)) {
                    /** @var array<string, mixed> $entry */
                    yield $entry;
                }
            }

            $pagination = $payload['meta']['pagination'] ?? null;
            if (! is_array($pagination)) {
                return;
            }

            $next = $pagination['next_page'] ?? null;
            if ($next === null) {
                return;
            }

            $page = (int) $next;
        }
    }

    public static function collectionKey(string $path): string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        return $segments === [] ? '' : (string) end($segments);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function request(string $path, array $query): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            $this->throttle();

            try {
                $this->requestCount++;
                $response = $this->http->get(ltrim($path, '/'), [
                    'query' => self::buildQuery($query),
                ]);

                $this->rememberRateLimit($response);

                return $response;
            } catch (ConnectException $e) {
                if (++$attempt > $this->maxRetries) {
                    throw $this->redact($e);
                }

                ($this->sleeper)(min(self::MAX_SLEEP_SECONDS, 2 ** $attempt));
            } catch (RequestException $e) {
                $response = $e->getResponse();
                $status = $response?->getStatusCode() ?? 0;

                if ($response !== null) {
                    $this->rememberRateLimit($response);
                }

                if ($status === 429) {
                    if (++$attempt > $this->maxRetries) {
                        throw $this->redact($e);
                    }

                    ($this->sleeper)($this->secondsUntilReset());
                    $this->rateLimitRemaining = null;
                    continue;
                }

                if ($status >= 500) {
                    if (++$attempt > $this->maxRetries) {
                        throw $this->redact($e);
                    }

                    ($this->sleeper)(min(self::MAX_SLEEP_SECONDS, 2 ** $attempt));
                    continue;
                }

                throw $this->redact($e);
            }
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function buildQuery(array $query): string
    {
        $pairs = [];
        foreach ($query as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $pairs[] = rawurlencode((string) $name) . '=' . rawurlencode(self::scalar($item));
                }
                continue;
            }

            $pairs[] = rawurlencode((string) $name) . '=' . rawurlencode(self::scalar($value));
        }

        return implode('&', $pairs);
    }

    private static function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        throw new RuntimeException('Query parameters must be scalar.');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The Hetzner API returned a response that is not a JSON object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function rememberRateLimit(ResponseInterface $response): void
    {
        $remaining = $response->getHeaderLine('RateLimit-Remaining');
        $reset = $response->getHeaderLine('RateLimit-Reset');

        $this->rateLimitRemaining = $remaining === '' ? null : (int) $remaining;
        $this->rateLimitReset = $reset === '' ? null : (int) $reset;
    }

    private function throttle(): void
    {
        if ($this->rateLimitRemaining === null || $this->rateLimitRemaining > self::RATE_LIMIT_FLOOR) {
            return;
        }

        $wait = $this->secondsUntilReset();
        if ($wait > 0) {
            ($this->sleeper)($wait);
            $this->rateLimitRemaining = null;
        }
    }

    private function secondsUntilReset(): int
    {
        if ($this->rateLimitReset === null) {
            return 1;
        }

        $wait = $this->rateLimitReset - time();

        return max(1, min(self::MAX_SLEEP_SECONDS, $wait));
    }

    private function redact(RequestException|ConnectException $e): RequestException|ConnectException
    {
        $request = $e->getRequest();
        if ($request->hasHeader('Authorization')) {
            $request = $request->withHeader('Authorization', 'Bearer ***');
        }

        if ($e instanceof ConnectException) {
            return new ConnectException($e->getMessage(), $request, $e->getPrevious(), $e->getHandlerContext());
        }

        return new RequestException($e->getMessage(), $request, $e->getResponse(), $e->getPrevious());
    }
}
