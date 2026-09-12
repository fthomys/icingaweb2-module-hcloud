<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class LoadBalancer extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_load_balancer';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'created',
            'algorithm_type',
            'outgoing_traffic',
            'ingoing_traffic',
            'included_traffic',
            'location_id',
            'load_balancer_type_id',
            'public_enabled',
            'public_ipv4',
            'public_ipv4_dns_ptr',
            'public_ipv6',
            'public_ipv6_dns_ptr',
            'protection_delete',
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
            'algorithm_type' => mt('hcloud', 'Algorithm Type'),
            'outgoing_traffic' => mt('hcloud', 'Outgoing Traffic'),
            'ingoing_traffic' => mt('hcloud', 'Ingoing Traffic'),
            'included_traffic' => mt('hcloud', 'Included Traffic'),
            'location_id' => mt('hcloud', 'Location ID'),
            'load_balancer_type_id' => mt('hcloud', 'Load Balancer Type ID'),
            'public_enabled' => mt('hcloud', 'Public Enabled'),
            'public_ipv4' => mt('hcloud', 'Public IPv4'),
            'public_ipv4_dns_ptr' => mt('hcloud', 'Public IPv4 DNS PTR'),
            'public_ipv6' => mt('hcloud', 'Public IPv6'),
            'public_ipv6_dns_ptr' => mt('hcloud', 'Public IPv6 DNS PTR'),
            'protection_delete' => mt('hcloud', 'Protection Delete'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created']));
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
        return ['name'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->belongsTo('location', Location::class)
            ->setCandidateKey(['project_id', 'location_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->belongsTo('load_balancer_type', LoadBalancerType::class)
            ->setCandidateKey(['project_id', 'load_balancer_type_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->hasMany('service', LoadBalancerService::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'load_balancer_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('target', LoadBalancerTarget::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'load_balancer_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('private_net', LoadBalancerPrivateNet::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'load_balancer_id'])
            ->setJoinType('LEFT');
    }
}
