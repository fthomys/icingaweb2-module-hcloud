<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class ServerType extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_server_type';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'cores',
            'memory',
            'disk',
            'storage_type',
            'cpu_type',
            'category',
            'architecture',
            'deprecation_announced',
            'deprecation_unavailable_after',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'name' => mt('hcloud', 'Name'),
            'description' => mt('hcloud', 'Description'),
            'cores' => mt('hcloud', 'Cores'),
            'memory' => mt('hcloud', 'Memory'),
            'disk' => mt('hcloud', 'Disk'),
            'storage_type' => mt('hcloud', 'Storage Type'),
            'cpu_type' => mt('hcloud', 'CPU Type'),
            'category' => mt('hcloud', 'Category'),
            'architecture' => mt('hcloud', 'Architecture'),
            'deprecation_announced' => mt('hcloud', 'Deprecation Announced'),
            'deprecation_unavailable_after' => mt('hcloud', 'Deprecation Unavailable After'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['deprecation_announced', 'deprecation_unavailable_after']));
    }

    /**
     * @return list<string>
     */
    public function getDefaultSort(): array
    {
        return ['name'];
    }

    /**
     * @return list<string>
     */
    public function getSearchColumns(): array
    {
        return ['name', 'description'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');
    }
}
