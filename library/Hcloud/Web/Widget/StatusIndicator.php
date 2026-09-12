<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Enum\HasLabel;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class StatusIndicator extends BaseHtmlElement
{
    protected $tag = 'span';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-status'];

    public function __construct(
        private readonly ?string $rawValue,
        private readonly ?HasLabel $state = null
    ) {
    }

    protected function assemble(): void
    {
        if ($this->rawValue === null || $this->rawValue === '') {
            $this->addHtml(Text::create('-'));

            return;
        }

        $cssClass = $this->state?->cssClass() ?? 'state-unknown';
        $label = $this->state?->label() ?? $this->rawValue;

        $this->addHtml(new HtmlElement(
            'span',
            Attributes::create(['class' => ['hcloud-status-ball', $cssClass]])
        ));

        $this->addHtml(new HtmlElement(
            'span',
            Attributes::create(['class' => 'hcloud-status-label']),
            Text::create($label)
        ));
    }
}
