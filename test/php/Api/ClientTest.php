<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Api;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Icinga\Module\Hcloud\Api\Client;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ClientTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $history = [];

    /** @var list<int> */
    private array $slept = [];

    public function testCollectionKeyIsTheLastPathSegment(): void
    {
        $this->assertSame('servers', Client::collectionKey('/servers'));
        $this->assertSame('rrsets', Client::collectionKey('/zones/42/rrsets'));
        $this->assertSame('subaccounts', Client::collectionKey('storage_boxes/7/subaccounts/'));
    }

    public function testPaginationWalksEveryPage(): void
    {
        $client = $this->client([
            $this->page('servers', [['id' => 1], ['id' => 2]], 2),
            $this->page('servers', [['id' => 3]], null),
        ]);

        $ids = array_column(iterator_to_array($client->getPaginated('/servers'), false), 'id');

        $this->assertSame([1, 2, 3], $ids);
        $this->assertCount(2, $this->history);
        $this->assertSame(2, $client->getRequestCount());
    }

    public function testPaginationStopsWhenTotalEntriesIsNull(): void
    {
        $body = [
            'servers' => [['id' => 9]],
            'meta' => ['pagination' => [
                'page' => 1,
                'per_page' => 50,
                'previous_page' => null,
                'next_page' => null,
                'last_page' => null,
                'total_entries' => null,
            ]],
        ];

        $client = $this->client([new Response(200, [], (string) json_encode($body))]);

        $this->assertCount(1, iterator_to_array($client->getPaginated('/servers'), false));
    }

    public function testPaginationStopsWhenMetaIsAbsent(): void
    {
        $client = $this->client([
            new Response(200, [], (string) json_encode(['servers' => [['id' => 1]]])),
        ]);

        $this->assertCount(1, iterator_to_array($client->getPaginated('/servers'), false));
    }

    public function testMissingCollectionKeyIsRejected(): void
    {
        $client = $this->client([new Response(200, [], (string) json_encode(['meta' => []]))]);

        $this->expectException(RuntimeException::class);
        iterator_to_array($client->getPaginated('/servers'), false);
    }

    public function testArrayQueryParametersAreRepeatedNotCommaJoined(): void
    {
        $client = $this->client([$this->page('servers', [], null)]);

        iterator_to_array($client->getPaginated('/servers', 'servers', ['status' => ['running', 'off']]), false);

        $query = (string) $this->history[0]['request']->getUri()->getQuery();

        $this->assertStringContainsString('status=running', $query);
        $this->assertStringContainsString('status=off', $query);
        $this->assertStringNotContainsString('running%2Coff', $query);
    }

    public function testPerPageIsSentAsFifty(): void
    {
        $client = $this->client([$this->page('servers', [], null)]);

        iterator_to_array($client->getPaginated('/servers'), false);

        $this->assertStringContainsString('per_page=50', (string) $this->history[0]['request']->getUri()->getQuery());
    }

    public function testRateLimitedRequestIsRetriedAfterTheResetHeader(): void
    {
        $reset = time() + 7;

        $client = $this->client([
            new Response(429, ['RateLimit-Remaining' => '0', 'RateLimit-Reset' => (string) $reset], '{"error":{}}'),
            $this->page('servers', [['id' => 1]], null),
        ]);

        $entries = iterator_to_array($client->getPaginated('/servers'), false);

        $this->assertCount(1, $entries);
        $this->assertCount(1, $this->slept);
        $this->assertGreaterThan(0, $this->slept[0]);
        $this->assertLessThanOrEqual(7, $this->slept[0]);
    }

    public function testServerErrorsAreRetried(): void
    {
        $client = $this->client([
            new Response(503, [], 'nope'),
            $this->page('servers', [['id' => 5]], null),
        ]);

        $this->assertCount(1, iterator_to_array($client->getPaginated('/servers'), false));
        $this->assertCount(1, $this->slept);
    }

    public function testClientErrorsFailFast(): void
    {
        $client = $this->client([
            new Response(404, [], '{"error":{"code":"not_found","message":"nope"}}'),
            $this->page('servers', [], null),
        ]);

        $this->expectException(RequestException::class);

        try {
            iterator_to_array($client->getPaginated('/servers'), false);
        } finally {
            $this->assertSame([], $this->slept);
        }
    }

    public function testTheTokenIsRedactedFromRethrownExceptions(): void
    {
        $client = $this->client([new Response(403, [], '{"error":{"code":"forbidden"}}')]);

        try {
            iterator_to_array($client->getPaginated('/servers'), false);
            $this->fail('Expected a RequestException.');
        } catch (RequestException $e) {
            $this->assertSame('Bearer ***', $e->getRequest()->getHeaderLine('Authorization'));
        }
    }

    public function testTheTokenIsSentAsABearerHeader(): void
    {
        $client = $this->client([$this->page('servers', [], null)]);

        iterator_to_array($client->getPaginated('/servers'), false);

        $this->assertSame('Bearer secret-token', $this->history[0]['request']->getHeaderLine('Authorization'));
    }

    /**
     * @param array<string, mixed> $entries
     */
    private function page(string $key, array $entries, ?int $nextPage): Response
    {
        $body = [
            $key => $entries,
            'meta' => ['pagination' => [
                'page' => 1,
                'per_page' => 50,
                'previous_page' => null,
                'next_page' => $nextPage,
                'last_page' => $nextPage,
                'total_entries' => null,
            ]],
        ];

        return new Response(200, [], (string) json_encode($body));
    }

    /**
     * @param list<Response> $responses
     */
    private function client(array $responses): Client
    {
        $this->history = [];
        $this->slept = [];

        $mock = new MockHandler($responses);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));

        return new Client(
            'secret-token',
            Client::CLOUD_BASE_URI,
            $stack,
            50,
            3,
            function (int $seconds): void {
                $this->slept[] = $seconds;
            }
        );
    }
}
