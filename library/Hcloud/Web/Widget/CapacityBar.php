<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Web\ValueFormatter;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

/**
 * A native progress element rather than a div whose width is set inline, because strict CSP
 * rejects the style attribute that the usual bar implementation depends on.
 */
class CapacityBar extends BaseHtmlElement
{
    public const WARNING_AT = 75.0;
    public const CRITICAL_AT = 90.0;

    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-capacity'];

    public function __construct(
        private readonly string $title,
        private readonly int $used,
        private readonly int $total
    ) {
    }

    public static function tryFor(string $title, mixed $used, mixed $total): ?self
    {
        if (! is_numeric($used) || ! is_numeric($total)) {
            return null;
        }

        $usedBytes = (int) $used;
        $totalBytes = (int) $total;

        if ($totalBytes <= 0 || $usedBytes < 0) {
            return null;
        }

        return new self($title, $usedBytes, $totalBytes);
    }

    private function percent(): float
    {
        return min(100.0, round(($this->used / $this->total) * 100, 1));
    }

    private function state(): string
    {
        $percent = $this->percent();

        if ($percent >= self::CRITICAL_AT) {
            return 'critical';
        }

        return $percent >= self::WARNING_AT ? 'warning' : 'ok';
    }

    protected function assemble(): void
    {
        $percent = $this->percent();

        $this->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'hcloud-capacity-head']),
            new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-capacity-title']),
                Text::create($this->title)
            ),
            new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-capacity-value']),
                Text::create(sprintf(
                    '%s / %s (%s)',
                    ValueFormatter::bytes($this->used),
                    ValueFormatter::bytes($this->total),
                    ValueFormatter::percentage($percent)
                ))
            )
        ));

        $this->addHtml(new HtmlElement('progress', Attributes::create([
            'class' => ['hcloud-capacity-bar', 'state-' . $this->state()],
            'value' => (string) $percent,
            'max' => '100',
        ])));
    }
}
