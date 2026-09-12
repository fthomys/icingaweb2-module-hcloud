<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Api\S3;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use RuntimeException;
use SimpleXMLElement;

/**
 * Hetzner exposes Object Storage through the S3 protocol only. There is no endpoint for the
 * Hetzner specific facts a console shows, so a bucket's size is what walking its object list
 * adds up to, and nothing here can report a quota because the service does not publish one.
 */
final class S3Client
{
    public const ENDPOINT_SUFFIX = 'your-objectstorage.com';
    public const DEFAULT_LOCATIONS = ['fsn1', 'nbg1', 'hel1'];

    private const PAGE_SIZE = 1000;

    private GuzzleClient $http;

    public function __construct(
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $location,
        ?HandlerStack $handler = null,
        private readonly int $timeout = 30
    ) {
        $options = [
            'base_uri' => $this->endpoint() . '/',
            'timeout' => $this->timeout,
            'connect_timeout' => 10,
            'http_errors' => true,
            'headers' => [
                'Accept-Encoding' => 'gzip',
                'User-Agent' => 'icingaweb2-module-hcloud',
            ],
        ];

        if ($handler !== null) {
            $options['handler'] = $handler;
        }

        $this->http = new GuzzleClient($options);
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function endpoint(): string
    {
        return sprintf('https://%s.%s', $this->location, self::ENDPOINT_SUFFIX);
    }

    private function host(): string
    {
        return sprintf('%s.%s', $this->location, self::ENDPOINT_SUFFIX);
    }

    /**
     * @param array<string, string> $query
     */
    private function get(string $path, array $query = []): SimpleXMLElement
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $amzDate = $now->format('Ymd\THis\Z');
        $date = $now->format('Ymd');

        $headers = [
            'host' => $this->host(),
            'x-amz-content-sha256' => SignatureV4::EMPTY_PAYLOAD,
            'x-amz-date' => $amzDate,
        ];

        $canonicalRequest = SignatureV4::canonicalRequest(
            'GET',
            $path,
            $query,
            $headers,
            SignatureV4::EMPTY_PAYLOAD
        );

        $headers['Authorization'] = SignatureV4::authorization(
            $this->accessKey,
            $this->secretKey,
            $this->location,
            $date,
            $amzDate,
            $canonicalRequest,
            $headers
        );

        unset($headers['host']);

        $response = $this->http->request('GET', ltrim($path, '/'), [
            'headers' => $headers,
            'query' => $query,
        ]);

        return self::parse((string) $response->getBody());
    }

    private static function parse(string $body): SimpleXMLElement
    {
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if ($xml === false) {
            throw new RuntimeException('The Object Storage endpoint returned a body that is not XML');
        }

        return $xml;
    }

    /**
     * @return list<array{name: string, created: ?string}>
     */
    public function listBuckets(): array
    {
        $xml = $this->get('/');
        $buckets = [];

        foreach ($xml->Buckets->Bucket ?? [] as $bucket) {
            $name = trim((string) $bucket->Name);
            if ($name === '') {
                continue;
            }

            $created = trim((string) $bucket->CreationDate);

            $buckets[] = ['name' => $name, 'created' => $created === '' ? null : $created];
        }

        usort($buckets, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $buckets;
    }

    /**
     * Sums the object list, because there is no endpoint that reports a bucket's size. A bucket
     * larger than the page budget returns what was counted so far with complete set to false,
     * so a partial figure is never mistaken for the real one.
     *
     * @return array{objects: int, bytes: int, complete: bool}
     */
    public function bucketUsage(string $bucket, int $maxPages): array
    {
        $objects = 0;
        $bytes = 0;
        $token = null;
        $pages = 0;

        do {
            if ($pages >= $maxPages) {
                return ['objects' => $objects, 'bytes' => $bytes, 'complete' => false];
            }

            $query = ['list-type' => '2', 'max-keys' => (string) self::PAGE_SIZE];
            if ($token !== null) {
                $query['continuation-token'] = $token;
            }

            $xml = $this->get('/' . $bucket, $query);
            $pages++;

            foreach ($xml->Contents ?? [] as $entry) {
                $objects++;
                $bytes += (int) $entry->Size;
            }

            $truncated = trim((string) $xml->IsTruncated) === 'true';
            $token = $truncated ? trim((string) $xml->NextContinuationToken) : null;
        } while ($token !== null && $token !== '');

        return ['objects' => $objects, 'bytes' => $bytes, 'complete' => true];
    }
}
