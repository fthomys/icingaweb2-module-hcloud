<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Common;

use Icinga\Application\Config;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;

final class Database
{
    public const MODULE_NAME = 'hcloud';

    private static ?Connection $connection = null;

    public static function get(): Connection
    {
        if (self::$connection === null) {
            self::$connection = self::create();
        }

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }

    public static function resourceName(): ?string
    {
        $configured = Config::module(self::MODULE_NAME)->get('db', 'resource');

        return is_string($configured) && $configured !== '' ? $configured : null;
    }

    private static function create(): Connection
    {
        $resourceName = self::resourceName();
        if ($resourceName === null) {
            throw new ConfigurationError(
                mt(self::MODULE_NAME, 'No database resource configured. Set it under Configuration.')
            );
        }

        $config = new SqlConfig(ResourceFactory::getResourceConfig($resourceName));
        if ($config->db === 'mysql') {
            $config->charset = 'utf8mb4';
        }

        return new Connection($config);
    }
}
