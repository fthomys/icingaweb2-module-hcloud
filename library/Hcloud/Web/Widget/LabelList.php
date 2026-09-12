<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class LabelList extends BaseHtmlElement
{
    protected $tag = 'span';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-labels'];

    /**
     * @param array<string, string> $labels
     */
    public function __construct(private readonly array $labels)
    {
    }

    protected function assemble(): void
    {
        if ($this->labels === []) {
            $this->addHtml(Text::create('-'));

            return;
        }

        foreach ($this->labels as $key => $value) {
            $this->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-label']),
                Text::create($value === '' ? $key : $key . '=' . $value)
            ));
        }
    }
}
