<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Db\Repository;
use Icinga\Module\Hcloud\Pricing\CostCalculator;
use Icinga\Module\Hcloud\Web\Controller;
use Icinga\Module\Hcloud\Web\Widget\CostBreakdown;

class CostsController extends Controller
{
    public function indexAction(): void
    {
        $this->setTitle($this->translate('Costs'));

        if (! $this->databaseIsReady()) {
            return;
        }

        $repository = new Repository($this->db());
        $calculator = new CostCalculator();

        $reports = [];
        foreach ($repository->projects() as $project) {
            $reports[$project['name']] = $calculator->calculate($repository->costInput($project['id']));
        }

        $this->addContent(new CostBreakdown($reports));
    }
}
