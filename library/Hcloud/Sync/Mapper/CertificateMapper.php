<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class CertificateMapper extends Mapper
{
    public static function resource(): string
    {
        return 'certificates';
    }

    public static function path(): string
    {
        return '/certificates';
    }

    public static function table(): string
    {
        return 'hcloud_certificate';
    }

    public static function childTables(): array
    {
        return ['hcloud_certificate_domain', 'hcloud_certificate_used_by'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'type' => $payload->str('type'),
            'not_valid_before' => $payload->time('not_valid_before'),
            'not_valid_after' => $payload->time('not_valid_after'),
            'fingerprint' => $payload->str('fingerprint'),
            'status_issuance' => $payload->str('status.issuance'),
            'status_renewal' => $payload->str('status.renewal'),
            'status_error_code' => $payload->str('status.error.code'),
            'status_error_message' => $payload->str('status.error.message'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $certificateId = $payload->int('id');

        $domains = [];
        foreach ($payload->strings('domain_names') as $domain) {
            $domains[] = ['certificate_id' => $certificateId, 'domain_name' => $domain];
        }

        $usedBy = [];
        foreach ($payload->each('used_by') as $user) {
            $usedById = $user->int('id');
            if ($usedById === null) {
                continue;
            }

            $usedBy[] = [
                'certificate_id' => $certificateId,
                'used_by_type' => $user->str('type') ?? 'unknown',
                'used_by_id' => $usedById,
            ];
        }

        return [
            'hcloud_certificate_domain' => $domains,
            'hcloud_certificate_used_by' => $usedBy,
        ];
    }
}
