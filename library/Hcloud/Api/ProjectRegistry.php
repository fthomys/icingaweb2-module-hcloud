<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Api;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Module\Hcloud\Api\S3\S3Client;
use Icinga\Module\Hcloud\Common\Database;

final class ProjectRegistry
{
    public const SECTION_PREFIX = 'project:';

    /**
     * @return list<Project>
     */
    public static function all(?Config $config = null): array
    {
        $config ??= Config::module(Database::MODULE_NAME);
        $projects = [];

        foreach ($config as $section => $values) {
            if (! is_string($section) || ! str_starts_with($section, self::SECTION_PREFIX)) {
                continue;
            }

            $key = substr($section, strlen(self::SECTION_PREFIX));
            if ($key === '') {
                continue;
            }

            $projects[] = self::fromSection($key, $values);
        }

        usort($projects, static fn (Project $a, Project $b): int => strcmp($a->key, $b->key));

        return $projects;
    }

    /**
     * @return list<Project>
     */
    public static function enabled(?Config $config = null): array
    {
        return array_values(array_filter(
            self::all($config),
            static fn (Project $project): bool => $project->enabled && $project->hasToken()
        ));
    }

    public static function byKey(string $key, ?Config $config = null): ?Project
    {
        foreach (self::all($config) as $project) {
            if ($project->key === $key) {
                return $project;
            }
        }

        return null;
    }

    public static function client(Project $project, string $baseUri = Client::CLOUD_BASE_URI): Client
    {
        return new Client($project->getToken(), $baseUri);
    }

    public static function storageClient(Project $project): Client
    {
        return self::client($project, Client::STORAGE_BASE_URI);
    }

    /**
     * @return list<Project>
     */
    public static function withObjectStorage(?Config $config = null): array
    {
        return array_values(array_filter(
            self::enabled($config),
            static fn (Project $project): bool => $project->hasS3Credentials()
        ));
    }

    /**
     * @return list<S3Client>
     */
    public static function objectStorageClients(Project $project): array
    {
        $clients = [];

        foreach ($project->s3Locations as $location) {
            $clients[] = new S3Client($project->getS3AccessKey(), $project->getS3SecretKey(), $location);
        }

        return $clients;
    }

    /**
     * @return list<string>
     */
    private static function locations(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return S3Client::DEFAULT_LOCATIONS;
        }

        $locations = [];
        foreach (explode(',', $raw) as $location) {
            $location = strtolower(trim($location));
            if ($location !== '' && ! in_array($location, $locations, true)) {
                $locations[] = $location;
            }
        }

        return $locations === [] ? S3Client::DEFAULT_LOCATIONS : $locations;
    }

    private static function fromSection(string $key, mixed $values): Project
    {
        $name = $key;
        $token = '';
        $enabled = true;
        $s3AccessKey = '';
        $s3SecretKey = '';
        $s3Locations = S3Client::DEFAULT_LOCATIONS;

        if ($values instanceof ConfigObject) {
            $configuredName = $values->get('name');
            if (is_string($configuredName) && $configuredName !== '') {
                $name = $configuredName;
            }

            $configuredToken = $values->get('token');
            if (is_string($configuredToken)) {
                $token = trim($configuredToken);
            }

            $enabled = (bool) $values->get('enabled', true);

            $configuredAccessKey = $values->get('s3_access_key');
            if (is_string($configuredAccessKey)) {
                $s3AccessKey = trim($configuredAccessKey);
            }

            $configuredSecretKey = $values->get('s3_secret_key');
            if (is_string($configuredSecretKey)) {
                $s3SecretKey = trim($configuredSecretKey);
            }

            $s3Locations = self::locations($values->get('s3_locations'));
        }

        return new Project($key, $name, $token, $enabled, $s3AccessKey, $s3SecretKey, $s3Locations);
    }
}
