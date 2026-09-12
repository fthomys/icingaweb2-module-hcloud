<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Certificate extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_certificate';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'created',
            'type',
            'not_valid_before',
            'not_valid_after',
            'fingerprint',
            'status_issuance',
            'status_renewal',
            'status_error_code',
            'status_error_message',
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
            'created' => mt('hcloud', 'Created'),
            'type' => mt('hcloud', 'Type'),
            'not_valid_before' => mt('hcloud', 'Not Valid Before'),
            'not_valid_after' => mt('hcloud', 'Not Valid After'),
            'fingerprint' => mt('hcloud', 'Fingerprint'),
            'status_issuance' => mt('hcloud', 'Status Issuance'),
            'status_renewal' => mt('hcloud', 'Status Renewal'),
            'status_error_code' => mt('hcloud', 'Status Error Code'),
            'status_error_message' => mt('hcloud', 'Status Error Message'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created', 'not_valid_before', 'not_valid_after']));
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
        return ['name', 'fingerprint'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->hasMany('domain', CertificateDomain::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'certificate_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('used_by', CertificateUsedBy::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'certificate_id'])
            ->setJoinType('LEFT');
    }
}
