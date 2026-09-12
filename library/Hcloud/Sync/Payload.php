<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;

final class Payload
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->data;
    }

    public function has(string $path): bool
    {
        return $this->resolve($path) !== null;
    }

    public function str(string $path): ?string
    {
        $value = $this->resolve($path);

        if ($value === null || is_array($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    public function int(string $path): ?int
    {
        $value = $this->resolve($path);

        if ($value === null || is_array($value) || is_bool($value)) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    public function num(string $path): ?string
    {
        $value = $this->resolve($path);

        if ($value === null || is_array($value) || is_bool($value)) {
            return null;
        }

        return is_numeric($value) ? (string) $value : null;
    }

    public function flag(string $path): int
    {
        return filter_var($this->resolve($path), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function time(string $path): ?string
    {
        return UtcDateTime::fromApi($this->resolve($path));
    }

    public function json(string $path): ?string
    {
        $value = $this->resolve($path);

        if (! is_array($value) || $value === []) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }

    public function child(string $path): ?self
    {
        $value = $this->resolve($path);

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return new self($value);
    }

    /**
     * @return list<self>
     */
    public function each(string $path): array
    {
        $value = $this->resolve($path);

        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                /** @var array<string, mixed> $item */
                $items[] = new self($item);
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function strings(string $path): array
    {
        $value = $this->resolve($path);

        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_scalar($item)) {
                $items[] = (string) $item;
            }
        }

        return $items;
    }

    /**
     * @return list<int>
     */
    public function ints(string $path): array
    {
        $value = $this->resolve($path);

        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $items[] = (int) $item;
            }
        }

        return $items;
    }

    /**
     * @return array{deprecation_announced: ?string, deprecation_unavailable_after: ?string}
     */
    public function deprecation(string $path = 'deprecation'): array
    {
        return [
            'deprecation_announced' => $this->time($path . '.announced'),
            'deprecation_unavailable_after' => $this->time($path . '.unavailable_after'),
        ];
    }

    private function resolve(string $path): mixed
    {
        $value = $this->data;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
