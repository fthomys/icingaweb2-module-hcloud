<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Hcloud\Api\ProjectRegistry;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Forms\DatabaseConfigForm;
use Icinga\Module\Hcloud\Forms\ProjectConfigForm;
use Icinga\Module\Hcloud\Forms\SyncConfigForm;
use Icinga\Module\Hcloud\Web\Widget\ProjectConfigList;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class ConfigController extends CompatController
{
    public function init(): void
    {
        $this->assertPermission('config/modules');
    }

    public function databaseAction(): void
    {
        $this->setTitle($this->translate('Database'));
        $this->addConfigTabs('database');

        $form = new DatabaseConfigForm(Config::module(Database::MODULE_NAME));

        $form->on(DatabaseConfigForm::ON_SUCCESS, function (): void {
            Database::reset();
            $this->redirectNow(Url::fromPath('hcloud/config/database'));
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function projectsAction(): void
    {
        $this->setTitle($this->translate('Hetzner Cloud Projects'));
        $this->addConfigTabs('projects');

        $key = $this->params->shift('project');
        $key = is_string($key) && $key !== '' ? $key : null;

        $config = Config::module(Database::MODULE_NAME);
        $form = new ProjectConfigForm($config, $key);

        $form->on(ProjectConfigForm::ON_SUCCESS, function (): void {
            $this->redirectNow(Url::fromPath('hcloud/config/projects'));
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent(new ProjectConfigList(ProjectRegistry::all($config), $key));
        $this->addContent($form);
    }

    public function syncAction(): void
    {
        $this->setTitle($this->translate('Sync Settings'));
        $this->addConfigTabs('sync');

        $form = new SyncConfigForm(Config::module(Database::MODULE_NAME));

        $form->on(SyncConfigForm::ON_SUCCESS, function (): void {
            $this->redirectNow(Url::fromPath('hcloud/config/sync'));
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    private function addConfigTabs(string $active): void
    {
        $tabs = $this->getTabs();

        $tabs->add('database', [
            'label' => $this->translate('Database'),
            'url' => 'hcloud/config/database',
        ]);

        $tabs->add('projects', [
            'label' => $this->translate('Projects'),
            'url' => 'hcloud/config/projects',
        ]);

        $tabs->add('sync', [
            'label' => $this->translate('Sync'),
            'url' => 'hcloud/config/sync',
        ]);

        $tabs->activate($active);
    }
}
