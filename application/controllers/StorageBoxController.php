<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\StorageBoxStatus;
use Icinga\Module\Hcloud\Model\StorageBox;
use Icinga\Module\Hcloud\Web\DetailController;
use Icinga\Module\Hcloud\Web\Widget\CapacityBar;
use ipl\Html\ValidHtml;
use ipl\Sql\Select;

class StorageBoxController extends DetailController
{
    protected function modelClass(): string
    {
        return StorageBox::class;
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => StorageBoxStatus::class];
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function capacity(array $values, int $projectId): ?ValidHtml
    {
        $typeId = $values['storage_box_type_id'] ?? null;
        if ($typeId === null) {
            return null;
        }

        $total = $this->db()->fetchScalar(
            (new Select())
                ->from('hcloud_storage_box_type')
                ->columns(['size'])
                ->where(['project_id = ?' => $projectId, 'id = ?' => $typeId])
        );

        return CapacityBar::tryFor($this->translate('Usage'), $values['stats_size'] ?? null, $total);
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Subaccounts') => [
                'table' => 'hcloud_storage_box_subaccount',
                'foreign' => 'storage_box_id',
                'columns' => ['username', 'home_directory', 'description', 'server'],
            ],
            $this->translate('Snapshots') => [
                'table' => 'hcloud_storage_box_snapshot',
                'foreign' => 'storage_box_id',
                'columns' => ['name', 'created', 'is_automatic', 'stats_size'],
            ],
        ];
    }
}
