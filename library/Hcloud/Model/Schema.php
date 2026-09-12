<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use DateTime;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

/**
 * @property int $id
 * @property string $version
 * @property DateTime $timestamp
 * @property bool $success
 * @property ?string $reason
 */
class Schema extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_schema';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'version',
            'timestamp',
            'success',
            'reason',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'version' => mt('hcloud', 'Version'),
            'timestamp' => mt('hcloud', 'Timestamp'),
            'success' => mt('hcloud', 'Success'),
            'reason' => mt('hcloud', 'Reason'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new BoolCast(['success']));
        $behaviors->add(new MillisecondTimestamp(['timestamp']));
    }
}
