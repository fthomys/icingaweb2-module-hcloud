<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

interface HasLabel
{
    public function label(): string;

    public function cssClass(): string;
}
