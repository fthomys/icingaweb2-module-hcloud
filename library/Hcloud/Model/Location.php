<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Relations;

class Location extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_location';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'country',
            'city',
            'latitude',
            'longitude',
            'network_zone',
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
            'country' => mt('hcloud', 'Country'),
            'city' => mt('hcloud', 'City'),
            'latitude' => mt('hcloud', 'Latitude'),
            'longitude' => mt('hcloud', 'Longitude'),
            'network_zone' => mt('hcloud', 'Network Zone'),
        ];
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
        return ['name', 'city', 'country'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');
    }
}
