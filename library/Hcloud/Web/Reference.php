<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

use ipl\Web\Url;

final class Reference
{
    public function __construct(
        public readonly string $title,
        public readonly string $label,
        public readonly ?Url $url
    ) {
    }
}
