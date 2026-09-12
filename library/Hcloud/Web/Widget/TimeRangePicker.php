<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Metric\TimeRange;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Link;
use ipl\Web\Url;

class TimeRangePicker extends BaseHtmlElement
{
    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-range-picker'];

    public function __construct(
        private readonly Url $baseUrl,
        private readonly TimeRange $active
    ) {
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'span',
            Attributes::create(['class' => 'hcloud-range-label']),
            Text::create(mt('hcloud', 'Range'))
        ));

        foreach (TimeRange::cases() as $range) {
            $url = (clone $this->baseUrl)->setParam(TimeRange::PARAM, $range->value);

            $classes = ['hcloud-range-option'];
            if ($range === $this->active) {
                $classes[] = 'active';
            }

            $this->addHtml(new Link($range->label(), $url, ['class' => $classes]));
        }
    }
}
