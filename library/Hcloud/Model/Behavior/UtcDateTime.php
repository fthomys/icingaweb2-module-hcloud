<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model\Behavior;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use ipl\Orm\Contract\PropertyBehavior;

class UtcDateTime extends PropertyBehavior
{
    public const FORMAT = 'Y-m-d H:i:s';

    public function fromDb($value, $key, $context)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        try {
            return new DateTime((string) $value, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return null;
        }
    }

    public function toDb($value, $key, $context)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return self::format($value);
        }

        try {
            return self::format(new DateTime((string) $value));
        } catch (Exception $e) {
            return null;
        }
    }

    public static function format(DateTimeInterface $value): string
    {
        $utc = DateTime::createFromInterface($value);
        $utc->setTimezone(new DateTimeZone('UTC'));

        return $utc->format(self::FORMAT);
    }

    public static function fromApi(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return self::format(new DateTime($value));
        } catch (Exception $e) {
            return null;
        }
    }
}
