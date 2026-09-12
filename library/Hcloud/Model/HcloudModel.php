<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

abstract class HcloudModel extends Model
{
    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return ['project_id', 'id'];
    }

    /**
     * @return list<string>
     */
    public function labelColumns(): array
    {
        return [];
    }
}
