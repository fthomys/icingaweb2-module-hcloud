<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Link;
use ipl\Web\Url;

class SetupHint extends BaseHtmlElement
{
    public const NO_RESOURCE = 'no-resource';
    public const NOT_REACHABLE = 'not-reachable';

    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-setup-hint'];

    public function __construct(
        private readonly string $reason,
        private readonly ?string $detail = null
    ) {
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'h2',
            Attributes::create([]),
            Text::create(mt('hcloud', 'Setup is not finished yet'))
        ));

        $message = $this->reason === self::NO_RESOURCE
            ? mt('hcloud', 'No database resource is selected for this module yet.')
            : mt('hcloud', 'The configured database cannot be read. Has the schema been applied?');

        $this->addHtml(new HtmlElement('p', Attributes::create([]), Text::create($message)));

        if ($this->detail !== null && $this->detail !== '') {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'hcloud-setup-detail']),
                Text::create($this->detail)
            ));
        }

        $steps = new HtmlElement('ol', Attributes::create(['class' => 'hcloud-setup-steps']));

        foreach (
            [
            mt('hcloud', 'Create a database and a user for it.'),
            mt('hcloud', 'Apply schema/mysql.sql or schema/pgsql.sql to that database.'),
            mt('hcloud', 'Create a matching resource under Configuration, Resources.'),
            mt('hcloud', 'Select that resource on the Database tab of this module.'),
            mt('hcloud', 'Add a project with a Hetzner API token, then run: icingacli hcloud sync run'),
            ] as $step
        ) {
            $steps->addHtml(new HtmlElement('li', Attributes::create([]), Text::create($step)));
        }

        $this->addHtml($steps);

        $this->addHtml(new Link(
            mt('hcloud', 'Configure this module'),
            Url::fromPath('hcloud/config/database'),
            ['class' => 'hcloud-setup-link']
        ));
    }
}
