<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum TargetHealthDetail: string implements HasLabel
{
    case Unspecified = 'unspecified';
    case Layer4NoConnection = 'layer4_no_connection';
    case Layer4Timeout = 'layer4_timeout';
    case Layer7Timeout = 'layer7_timeout';
    case UnexpectedHttpStatus = 'unexpected_http_status';
    case UnexpectedHttpContent = 'unexpected_http_content';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Unspecified => mt('hcloud', 'Unspecified'),
            self::Layer4NoConnection => mt('hcloud', 'No connection on layer 4'),
            self::Layer4Timeout => mt('hcloud', 'Timeout on layer 4'),
            self::Layer7Timeout => mt('hcloud', 'Timeout on layer 7'),
            self::UnexpectedHttpStatus => mt('hcloud', 'Unexpected HTTP status'),
            self::UnexpectedHttpContent => mt('hcloud', 'Unexpected HTTP content'),
        };
    }

    public function cssClass(): string
    {
        return $this === self::Unspecified ? 'state-unknown' : 'state-critical';
    }
}
