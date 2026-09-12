<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Image extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_image';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'type',
            'status',
            'created',
            'image_size',
            'disk_size',
            'architecture',
            'os_flavor',
            'os_version',
            'rapid_deploy',
            'bound_to',
            'created_from_id',
            'created_from_name',
            'deleted',
            'protection_delete',
            'deprecation_announced',
            'deprecation_unavailable_after',
            'labels',
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
            'type' => mt('hcloud', 'Type'),
            'status' => mt('hcloud', 'Status'),
            'created' => mt('hcloud', 'Created'),
            'image_size' => mt('hcloud', 'Image Size'),
            'disk_size' => mt('hcloud', 'Disk Size'),
            'architecture' => mt('hcloud', 'Architecture'),
            'os_flavor' => mt('hcloud', 'OS Flavor'),
            'os_version' => mt('hcloud', 'OS Version'),
            'rapid_deploy' => mt('hcloud', 'Rapid Deploy'),
            'bound_to' => mt('hcloud', 'Bound To'),
            'created_from_id' => mt('hcloud', 'Created From ID'),
            'created_from_name' => mt('hcloud', 'Created From Name'),
            'deleted' => mt('hcloud', 'Deleted'),
            'protection_delete' => mt('hcloud', 'Protection Delete'),
            'deprecation_announced' => mt('hcloud', 'Deprecation Announced'),
            'deprecation_unavailable_after' => mt('hcloud', 'Deprecation Unavailable After'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime([
            'created',
            'deleted',
            'deprecation_announced',
            'deprecation_unavailable_after',
        ]));
        $behaviors->add(new Json(['labels']));
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
        return ['name', 'description', 'os_flavor'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');
    }
}
