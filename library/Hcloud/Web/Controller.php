<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Web\Widget\SetupHint;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use ipl\Web\Compat\CompatController;
use ipl\Web\Compat\SearchControls;
use Throwable;

abstract class Controller extends CompatController
{
    use SearchControls;

    public function init(): void
    {
        $this->assertPermission('hcloud/read');
    }

    protected function db(): Connection
    {
        return Database::get();
    }

    /**
     * Render a setup hint instead of the view when the module is not usable yet.
     *
     * Returns true when the caller may go on and query the database.
     */
    protected function databaseIsReady(): bool
    {
        if (Database::resourceName() === null) {
            $this->addContent(new SetupHint(SetupHint::NO_RESOURCE));

            return false;
        }

        try {
            $this->db()->fetchScalar((new Select())->from('hcloud_schema')->columns(['COUNT(*)']));
        } catch (Throwable $e) {
            $this->addContent(new SetupHint(SetupHint::NOT_REACHABLE, $e->getMessage()));

            return false;
        }

        return true;
    }
}
