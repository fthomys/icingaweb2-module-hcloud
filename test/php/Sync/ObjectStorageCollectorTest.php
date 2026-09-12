<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Sync;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Icinga\Module\Hcloud\Api\S3\S3Client;
use Icinga\Module\Hcloud\Db\Store;
use Icinga\Module\Hcloud\Sync\ObjectStorageCollector;
use Icinga\Module\Hcloud\Sync\SyncOptions;
use PHPUnit\Framework\TestCase;
use ipl\Sql\Connection;

/**
 * The dry run never reaches the database, which is what lets this run without one. It is also
 * the path that has to stay honest: a dry run that writes is the bug this guards against.
 */
final class ObjectStorageCollectorTest extends TestCase
{
    private static function store(): Store
    {
        return new Store(new Connection([
            'db' => 'mysql',
            'host' => '127.0.0.1',
            'dbname' => 'unreachable',
            'username' => 'none',
            'password' => 'none',
        ]));
    }

    /**
     * @param list<Response> $responses
     */
    private static function s3(string $location, array $responses): S3Client
    {
        return new S3Client('AKID', 'secret', $location, HandlerStack::create(new MockHandler($responses)));
    }

    private static function bucketList(string ...$names): Response
    {
        $entries = '';
        foreach ($names as $name) {
            $entries .= sprintf(
                '<Bucket><Name>%s</Name><CreationDate>2026-01-01T00:00:00.000Z</CreationDate></Bucket>',
                $name
            );
        }

        return new Response(
            200,
            [],
            '<?xml version="1.0"?><ListAllMyBucketsResult><Buckets>' . $entries . '</Buckets></ListAllMyBucketsResult>'
        );
    }

    public function testItCountsBucketsFromEveryConfiguredLocation(): void
    {
        $collector = new ObjectStorageCollector(
            self::store(),
            [
                self::s3('fsn1', [self::bucketList('media')]),
                self::s3('nbg1', [self::bucketList('backups', 'logs')]),
            ],
            new SyncOptions(objectStorageUsage: false)
        );

        $counts = $collector->collect(1, true);

        $this->assertSame(3, $counts['fetched']);
        $this->assertSame(0, $counts['written']);
        $this->assertSame(0, $counts['deleted']);
    }

    public function testItSkipsTheObjectWalkWhenUsageIsOff(): void
    {
        $collector = new ObjectStorageCollector(
            self::store(),
            [self::s3('fsn1', [self::bucketList('media')])],
            new SyncOptions(objectStorageUsage: false)
        );

        $this->assertSame(1, $collector->collect(1, true)['fetched']);
    }

    public function testItWalksObjectsWhenUsageIsOn(): void
    {
        $listing = '<?xml version="1.0"?><ListBucketResult><IsTruncated>false</IsTruncated>'
            . '<Contents><Key>a</Key><Size>42</Size></Contents></ListBucketResult>';

        $collector = new ObjectStorageCollector(
            self::store(),
            [self::s3('fsn1', [self::bucketList('media'), new Response(200, [], $listing)])],
            new SyncOptions(objectStorageUsage: true, objectStorageMaxPages: 5)
        );

        $this->assertSame(1, $collector->collect(1, true)['fetched']);
    }
}
