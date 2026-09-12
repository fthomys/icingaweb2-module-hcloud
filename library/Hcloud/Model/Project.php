<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use DateTime;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * @property int $id
 * @property string $config_key
 * @property string $name
 * @property int $enabled
 * @property DateTime $created
 */
class Project extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_project';
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
        return ['config_key', 'name', 'enabled', 'created'];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'config_key' => mt('hcloud', 'Config Key'),
            'name' => mt('hcloud', 'Name'),
            'enabled' => mt('hcloud', 'Enabled'),
            'created' => mt('hcloud', 'Created'),
        ];
    }

    /**
     * @return list<string>
     */
    public function getSearchColumns(): array
    {
        return ['name', 'config_key'];
    }

    /**
     * @return list<string>
     */
    public function getDefaultSort(): array
    {
        return ['name'];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created']));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasMany('server', Server::class)->setJoinType('LEFT');
        $relations->hasMany('volume', Volume::class)->setJoinType('LEFT');
        $relations->hasMany('sync_run', SyncRun::class)->setJoinType('LEFT');
    }
}
