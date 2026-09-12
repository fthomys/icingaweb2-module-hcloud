<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\ActionStatus;
use Icinga\Module\Hcloud\Model\Action;
use Icinga\Module\Hcloud\Web\ResourceController;

class ActionsController extends ResourceController
{
    protected function modelClass(): string
    {
        return Action::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Activity');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['command', 'status', 'progress', 'started', 'finished', 'error_code'];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => ActionStatus::class];
    }

    protected function detailUrl(): ?string
    {
        return '';
    }
}
