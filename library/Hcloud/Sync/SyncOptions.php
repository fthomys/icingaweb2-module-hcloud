<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use Icinga\Application\Config;
use Icinga\Module\Hcloud\Common\Database;

final class SyncOptions
{
    public function __construct(
        public readonly int $metricRetentionHours = 48,
        public readonly int $actionRetentionDays = 30,
        public readonly int $perPage = 50,
        public readonly int $metricStep = 300,
        public readonly bool $objectStorageUsage = true,
        public readonly int $objectStorageMaxPages = 20,
        public readonly bool $dryRun = false,
        public readonly ?string $onlyResource = null,
        public readonly bool $withMetrics = true
    ) {
    }

    public static function fromConfig(?Config $config = null): self
    {
        $config ??= Config::module(Database::MODULE_NAME);

        return new self(
            metricRetentionHours: (int) ($config->get('sync', 'retention_metrics') ?? 48),
            actionRetentionDays: (int) ($config->get('sync', 'retention_actions') ?? 30),
            perPage: (int) ($config->get('sync', 'per_page') ?? 50),
            metricStep: (int) ($config->get('sync', 'metric_step') ?? 300),
            objectStorageUsage: (bool) ($config->get('sync', 'object_storage_usage') ?? true),
            objectStorageMaxPages: (int) ($config->get('sync', 'object_storage_max_pages') ?? 20)
        );
    }

    public function with(
        ?bool $dryRun = null,
        ?string $onlyResource = null,
        ?bool $withMetrics = null
    ): self {
        return new self(
            metricRetentionHours: $this->metricRetentionHours,
            actionRetentionDays: $this->actionRetentionDays,
            perPage: $this->perPage,
            metricStep: $this->metricStep,
            objectStorageUsage: $this->objectStorageUsage,
            objectStorageMaxPages: $this->objectStorageMaxPages,
            dryRun: $dryRun ?? $this->dryRun,
            onlyResource: $onlyResource ?? $this->onlyResource,
            withMetrics: $withMetrics ?? $this->withMetrics
        );
    }

    public function wants(string $resource): bool
    {
        return $this->onlyResource === null || $this->onlyResource === $resource;
    }
}
