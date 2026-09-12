<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class MetricSample extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_metric_sample';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'resource_type',
            'resource_id',
            'series_name',
            'ts',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'value',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'resource_type' => mt('hcloud', 'Resource Type'),
            'resource_id' => mt('hcloud', 'Resource ID'),
            'series_name' => mt('hcloud', 'Series Name'),
            'ts' => mt('hcloud', 'Ts'),
            'value' => mt('hcloud', 'Value'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['ts']));
    }
}
