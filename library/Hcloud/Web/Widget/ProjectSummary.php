<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class ProjectSummary extends BaseHtmlElement
{
    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-summary'];

    /**
     * @param list<array{name: string, last_sync: ?string, counts: array<string, int>,
     *     problems: array<string, int>}> $projects
     * @param list<string> $unsynced Names of configured projects that have never been synced
     */
    public function __construct(
        private readonly array $projects,
        private readonly array $unsynced = []
    ) {
    }

    protected function assemble(): void
    {
        if ($this->unsynced !== []) {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'hcloud-summary-pending']),
                Text::create(sprintf(
                    mt('hcloud', 'Configured but never synced: %s. Run a sync to populate these.'),
                    implode(', ', $this->unsynced)
                ))
            ));
        }

        if ($this->projects === []) {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'empty-state']),
                Text::create($this->unsynced === []
                    ? mt('hcloud', 'No Hetzner Cloud project is configured yet.')
                    : mt('hcloud', 'Nothing has been synced yet.'))
            ));

            return;
        }

        foreach ($this->projects as $project) {
            $card = new HtmlElement('div', Attributes::create(['class' => 'hcloud-summary-card']));

            $card->addHtml(new HtmlElement('h2', Attributes::create([]), Text::create($project['name'])));

            $card->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'hcloud-summary-sync']),
                Text::create($project['last_sync'] === null
                    ? mt('hcloud', 'Never synced')
                    : sprintf(mt('hcloud', 'Last sync: %s'), $project['last_sync']))
            ));

            $card->addHtml($this->tiles($project['counts'], 'hcloud-tile'));

            if ($project['problems'] !== []) {
                $card->addHtml($this->tiles($project['problems'], 'hcloud-tile hcloud-tile-problem'));
            }

            $this->addHtml($card);
        }
    }

    /**
     * @param array<string, int> $counts
     */
    private function tiles(array $counts, string $cssClass): HtmlElement
    {
        $list = new HtmlElement('div', Attributes::create(['class' => 'hcloud-tiles']));

        foreach ($counts as $label => $value) {
            $tile = new HtmlElement('div', Attributes::create(['class' => $cssClass]));
            $tile->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-tile-value']),
                Text::create((string) $value)
            ));
            $tile->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-tile-label']),
                Text::create($label)
            ));

            $list->addHtml($tile);
        }

        return $list;
    }
}
