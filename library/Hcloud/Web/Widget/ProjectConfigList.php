<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Api\Project;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Web\Widget\Link;
use ipl\Web\Url;

class ProjectConfigList extends BaseHtmlElement
{
    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-project-config'];

    /**
     * @param list<Project> $projects
     */
    public function __construct(
        private readonly array $projects,
        private readonly ?string $activeKey = null
    ) {
    }

    protected function assemble(): void
    {
        if ($this->projects === []) {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'empty-state']),
                Text::create(mt('hcloud', 'No project configured yet. Add the first one below.'))
            ));

            return;
        }

        $table = new Table();
        $table->addAttributes(['class' => ['common-table', 'table-row-selectable']]);

        $table->getHeader()->add(Table::row([
            mt('hcloud', 'Key'),
            mt('hcloud', 'Name'),
            mt('hcloud', 'Token'),
            mt('hcloud', 'Enabled'),
        ], null, 'th'));

        foreach ($this->projects as $project) {
            $row = Table::row([
                new Link($project->key, Url::fromPath('hcloud/config/projects', ['project' => $project->key])),
                $project->name,
                $project->hasToken() ? mt('hcloud', 'Stored') : mt('hcloud', 'Missing'),
                $project->enabled ? mt('hcloud', 'Yes') : mt('hcloud', 'No'),
            ]);

            if ($project->key === $this->activeKey) {
                $row->addAttributes(['class' => 'active']);
            }

            $table->getBody()->add($row);
        }

        $this->addHtml($table);

        if ($this->activeKey !== null) {
            $this->addHtml(new Link(
                mt('hcloud', 'Add another project'),
                Url::fromPath('hcloud/config/projects'),
                ['class' => 'hcloud-add-project']
            ));
        }
    }
}
