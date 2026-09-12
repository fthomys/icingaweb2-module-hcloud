<?php

/** @var \Icinga\Application\Modules\Module $this */

use Icinga\Application\Hook\DbMigrationHook;
use Icinga\Application\Hook\HealthHook;
use Icinga\Module\Hcloud\ProvidedHook\DbMigration;
use Icinga\Module\Hcloud\ProvidedHook\Director\LoadBalancerImportSource;
use Icinga\Module\Hcloud\ProvidedHook\Director\PrimaryIpImportSource;
use Icinga\Module\Hcloud\ProvidedHook\Director\ServerImportSource;
use Icinga\Module\Hcloud\ProvidedHook\Director\StorageBoxImportSource;
use Icinga\Module\Hcloud\ProvidedHook\Director\VolumeImportSource;
use Icinga\Module\Hcloud\ProvidedHook\Health;

$this->provideHook(DbMigrationHook::class, DbMigration::class);
$this->provideHook(HealthHook::class, Health::class);

$this->provideHook('director/ImportSource', ServerImportSource::class);
$this->provideHook('director/ImportSource', LoadBalancerImportSource::class);
$this->provideHook('director/ImportSource', VolumeImportSource::class);
$this->provideHook('director/ImportSource', PrimaryIpImportSource::class);
$this->provideHook('director/ImportSource', StorageBoxImportSource::class);
