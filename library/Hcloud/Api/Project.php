<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Api;

final class Project
{
    /**
     * @param list<string> $s3Locations
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        private readonly string $token,
        public readonly bool $enabled,
        private readonly string $s3AccessKey = '',
        private readonly string $s3SecretKey = '',
        public readonly array $s3Locations = []
    ) {
    }

    public function getS3AccessKey(): string
    {
        return $this->s3AccessKey;
    }

    public function getS3SecretKey(): string
    {
        return $this->s3SecretKey;
    }

    public function hasS3Credentials(): bool
    {
        return $this->s3AccessKey !== '' && $this->s3SecretKey !== '';
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function hasToken(): bool
    {
        return $this->token !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'token' => $this->token === '' ? '' : '***',
            'enabled' => $this->enabled,
            's3_access_key' => $this->s3AccessKey === '' ? '' : '***',
            's3_secret_key' => $this->s3SecretKey === '' ? '' : '***',
            's3_locations' => $this->s3Locations,
        ];
    }
}
