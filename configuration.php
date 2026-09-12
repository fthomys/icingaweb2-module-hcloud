<?php

/** @var \Icinga\Application\Modules\Module $this */

$this->providePermission('hcloud/read', $this->translate('Allow browsing synced Hetzner Cloud resources'));
$this->providePermission('hcloud/config', $this->translate('Allow configuring Hetzner Cloud projects'));
$this->providePermission('hcloud/sync', $this->translate('Allow starting a sync from the web interface'));

$section = $this->menuSection(N_('Hetzner Cloud'))
    ->setIcon('cloud')
    ->setUrl('hcloud/dashboard')
    ->setPriority(60);

$section->add(N_('Dashboard'))->setUrl('hcloud/dashboard')->setPriority(10);
$section->add(N_('Servers'))->setUrl('hcloud/servers')->setPriority(20);
$section->add(N_('Volumes'))->setUrl('hcloud/volumes')->setPriority(30);
$section->add(N_('Load Balancers'))->setUrl('hcloud/load-balancers')->setPriority(40);
$section->add(N_('Networks'))->setUrl('hcloud/networks')->setPriority(50);
$section->add(N_('Firewalls'))->setUrl('hcloud/firewalls')->setPriority(60);
$section->add(N_('IP Addresses'))->setUrl('hcloud/ips')->setPriority(70);
$section->add(N_('Certificates'))->setUrl('hcloud/certificates')->setPriority(80);
$section->add(N_('DNS Zones'))->setUrl('hcloud/zones')->setPriority(90);
$section->add(N_('Storage Boxes'))->setUrl('hcloud/storage-boxes')->setPriority(100);
$section->add(N_('Object Storage'))->setUrl('hcloud/buckets')->setPriority(105);
$section->add(N_('Images'))->setUrl('hcloud/images')->setPriority(110);
$section->add(N_('SSH Keys'))->setUrl('hcloud/ssh-keys')->setPriority(120);
$section->add(N_('Costs'))->setUrl('hcloud/costs')->setPriority(130);
$section->add(N_('Activity'))->setUrl('hcloud/actions')->setPriority(140);
$section->add(N_('Sync'))->setUrl('hcloud/sync')->setPriority(150);

$this->provideConfigTab('database', [
    'title' => $this->translate('Configure the hcloud database resource'),
    'label' => $this->translate('Database'),
    'url' => 'config/database',
]);

$this->provideConfigTab('projects', [
    'title' => $this->translate('Configure Hetzner Cloud projects'),
    'label' => $this->translate('Projects'),
    'url' => 'config/projects',
]);

$this->provideConfigTab('sync', [
    'title' => $this->translate('Configure how often and how much is synced'),
    'label' => $this->translate('Sync'),
    'url' => 'config/sync',
]);
