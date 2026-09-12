<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Forms\SyncNowForm;
use Icinga\Module\Hcloud\Model\SyncRun;
use Icinga\Module\Hcloud\Web\ResourceController;
use Icinga\Web\Notification;
use ipl\Web\Url;

class SyncController extends ResourceController
{
    protected function modelClass(): string
    {
        return SyncRun::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Sync Runs');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return [
            'started',
            'ended',
            'status',
            'resources_synced',
            'rows_written',
            'rows_deleted',
            'api_requests',
            'error',
        ];
    }

    public function indexAction(): void
    {
        if ($this->hasPermission('hcloud/sync')) {
            $form = new SyncNowForm();

            $form->on(SyncNowForm::ON_SUCCESS, function (SyncNowForm $form): void {
                $failure = $form->getFailure();

                if ($failure !== null) {
                    Notification::error($failure);
                } else {
                    Notification::success((string) $form->getSummary());
                }

                $this->redirectNow(Url::fromPath('hcloud/sync'));
            });

            $form->handleRequest($this->getServerRequest());

            $this->addControl($form);
        }

        parent::indexAction();
    }
}
