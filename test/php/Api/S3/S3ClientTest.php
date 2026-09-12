<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Api\S3;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Icinga\Module\Hcloud\Api\S3\S3Client;
use PHPUnit\Framework\TestCase;

final class S3ClientTest extends TestCase
{
    /**
     * @param list<Response> $responses
     * @param list<array{request: Request}> $history
     */
    private static function client(array $responses, array &$history): S3Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        return new S3Client('AKID', 'secret', 'fsn1', $stack);
    }

    public function testItDerivesTheEndpointFromTheLocation(): void
    {
        $history = [];

        $this->assertSame(
            'https://fsn1.your-objectstorage.com',
            self::client([], $history)->endpoint()
        );
    }

    public function testListBucketsReadsTheNamesAndCreationDates(): void
    {
        $history = [];
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <ListAllMyBucketsResult>
          <Owner><ID>owner</ID></Owner>
          <Buckets>
            <Bucket><Name>media</Name><CreationDate>2026-01-02T03:04:05.000Z</CreationDate></Bucket>
            <Bucket><Name>backups</Name><CreationDate>2025-12-01T00:00:00.000Z</CreationDate></Bucket>
          </Buckets>
        </ListAllMyBucketsResult>
        XML;

        $buckets = self::client([new Response(200, [], $xml)], $history)->listBuckets();

        $this->assertSame(
            [
                ['name' => 'backups', 'created' => '2025-12-01T00:00:00.000Z'],
                ['name' => 'media', 'created' => '2026-01-02T03:04:05.000Z'],
            ],
            $buckets
        );
    }

    public function testEveryRequestCarriesASignedAuthorizationHeader(): void
    {
        $history = [];
        $xml = '<?xml version="1.0"?><ListAllMyBucketsResult><Buckets/></ListAllMyBucketsResult>';

        self::client([new Response(200, [], $xml)], $history)->listBuckets();

        $request = $history[0]['request'];

        $this->assertStringStartsWith('AWS4-HMAC-SHA256 Credential=AKID/', $request->getHeaderLine('Authorization'));
        $this->assertStringContainsString('/fsn1/s3/aws4_request', $request->getHeaderLine('Authorization'));
        $this->assertNotSame('', $request->getHeaderLine('x-amz-date'));
        $this->assertSame(
            'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            $request->getHeaderLine('x-amz-content-sha256')
        );
    }

    public function testBucketUsageSumsEveryPage(): void
    {
        $history = [];
        $first = <<<XML
        <?xml version="1.0"?>
        <ListBucketResult>
          <IsTruncated>true</IsTruncated>
          <NextContinuationToken>token-2</NextContinuationToken>
          <Contents><Key>a</Key><Size>100</Size></Contents>
          <Contents><Key>b</Key><Size>250</Size></Contents>
        </ListBucketResult>
        XML;
        $second = <<<XML
        <?xml version="1.0"?>
        <ListBucketResult>
          <IsTruncated>false</IsTruncated>
          <Contents><Key>c</Key><Size>650</Size></Contents>
        </ListBucketResult>
        XML;

        $usage = self::client(
            [new Response(200, [], $first), new Response(200, [], $second)],
            $history
        )->bucketUsage('media', 10);

        $this->assertSame(['objects' => 3, 'bytes' => 1000, 'complete' => true], $usage);
        $this->assertCount(2, $history);
        $this->assertStringContainsString('continuation-token=token-2', (string) $history[1]['request']->getUri());
    }

    public function testBucketUsageStopsAtThePageBudgetAndSaysSo(): void
    {
        $history = [];
        $page = <<<XML
        <?xml version="1.0"?>
        <ListBucketResult>
          <IsTruncated>true</IsTruncated>
          <NextContinuationToken>more</NextContinuationToken>
          <Contents><Key>a</Key><Size>5</Size></Contents>
        </ListBucketResult>
        XML;

        $usage = self::client(
            [new Response(200, [], $page), new Response(200, [], $page)],
            $history
        )->bucketUsage('media', 2);

        $this->assertFalse($usage['complete']);
        $this->assertSame(2, $usage['objects']);
        $this->assertSame(10, $usage['bytes']);
        $this->assertCount(2, $history);
    }

    public function testAnEmptyBucketCountsAsComplete(): void
    {
        $history = [];
        $xml = '<?xml version="1.0"?><ListBucketResult><IsTruncated>false</IsTruncated></ListBucketResult>';

        $usage = self::client([new Response(200, [], $xml)], $history)->bucketUsage('media', 5);

        $this->assertSame(['objects' => 0, 'bytes' => 0, 'complete' => true], $usage);
    }
}
