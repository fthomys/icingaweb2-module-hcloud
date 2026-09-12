<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum CertificateType: string implements HasLabel
{
    case Uploaded = 'uploaded';
    case Managed = 'managed';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => mt('hcloud', 'Uploaded'),
            self::Managed => mt('hcloud', 'Managed'),
        };
    }

    public function cssClass(): string
    {
        return 'certificate-type-' . $this->value;
    }
}
