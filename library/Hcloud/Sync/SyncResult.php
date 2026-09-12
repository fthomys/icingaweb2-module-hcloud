<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

final class SyncResult
{
    public int $resources = 0;

    public int $written = 0;

    public int $deleted = 0;

    public int $apiRequests = 0;

    /** @var array<string, string> */
    public array $errors = [];

    public function add(string $resource, int $written, int $deleted): void
    {
        $this->resources++;
        $this->written += $written;
        $this->deleted += $deleted;
    }

    public function fail(string $resource, string $message): void
    {
        $this->errors[$resource] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function errorSummary(): ?string
    {
        if ($this->errors === []) {
            return null;
        }

        $lines = [];
        foreach ($this->errors as $resource => $message) {
            $lines[] = $resource . ': ' . $message;
        }

        return implode("\n", $lines);
    }
}
