<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use DateTimeImmutable;
use DateTimeZone;
use Icinga\Module\Hcloud\Api\S3\S3Client;
use Icinga\Module\Hcloud\Db\Store;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;

/**
 * Object Storage is reachable over S3 only, and a bucket lives in exactly one location, so the
 * inventory is the union of what every configured location answers. Usage is what the object
 * listing adds up to, which is why it can be switched off for accounts where that walk is too
 * expensive to run on every sync.
 */
final class ObjectStorageCollector
{
    public const RESOURCE = 'object_storage_buckets';

    private const TABLE = 'hcloud_object_storage_bucket';

    /**
     * @param list<S3Client> $clients
     */
    public function __construct(
        private readonly Store $store,
        private readonly array $clients,
        private readonly SyncOptions $options
    ) {
    }

    /**
     * @return array{written: int, deleted: int, fetched: int}
     */
    public function collect(int $projectId, bool $dryRun): array
    {
        $rows = [];
        $scanned = UtcDateTime::format(new DateTimeImmutable('now', new DateTimeZone('UTC')));

        foreach ($this->clients as $client) {
            foreach ($client->listBuckets() as $bucket) {
                $row = [
                    'id' => $client->getLocation() . '/' . $bucket['name'],
                    'location' => $client->getLocation(),
                    'name' => $bucket['name'],
                    'created' => self::timestamp($bucket['created']),
                    'object_count' => null,
                    'size' => null,
                    'usage_complete' => 0,
                    'usage_scanned' => null,
                ];

                if ($this->options->objectStorageUsage) {
                    $usage = $client->bucketUsage($bucket['name'], $this->options->objectStorageMaxPages);

                    $row['object_count'] = $usage['objects'];
                    $row['size'] = $usage['bytes'];
                    $row['usage_complete'] = $usage['complete'] ? 1 : 0;
                    $row['usage_scanned'] = $scanned;
                }

                $rows[] = $row;
            }
        }

        if ($dryRun) {
            return ['written' => 0, 'deleted' => 0, 'fetched' => count($rows)];
        }

        $counts = $this->store->replace(self::TABLE, $projectId, $rows);

        return $counts + ['fetched' => count($rows)];
    }

    private static function timestamp(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $parsed = date_create_immutable($raw);

        return $parsed === false ? null : UtcDateTime::format($parsed->setTimezone(new DateTimeZone('UTC')));
    }
}
