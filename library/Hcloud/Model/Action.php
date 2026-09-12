<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Action extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_action';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'command',
            'status',
            'progress',
            'started',
            'finished',
            'error_code',
            'error_message',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'command' => mt('hcloud', 'Command'),
            'status' => mt('hcloud', 'Status'),
            'progress' => mt('hcloud', 'Progress'),
            'started' => mt('hcloud', 'Started'),
            'finished' => mt('hcloud', 'Finished'),
            'error_code' => mt('hcloud', 'Error Code'),
            'error_message' => mt('hcloud', 'Error Message'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['started', 'finished']));
    }

    /**
     * @return list<string>
     */
    public function getDefaultSort(): array
    {
        return ['started DESC'];
    }

    /**
     * @return list<string>
     */
    public function getSearchColumns(): array
    {
        return ['command', 'status'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->hasMany('resource', ActionResource::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'action_id'])
            ->setJoinType('LEFT');
    }
}
