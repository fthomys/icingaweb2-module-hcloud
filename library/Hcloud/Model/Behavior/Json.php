<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class Json extends PropertyBehavior
{
    public function fromDb($value, $key, $context)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function toDb($value, $key, $context)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }
}
