<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Enum\HasLabel;
use Icinga\Module\Hcloud\Web\ColumnFormat;
use Icinga\Module\Hcloud\Web\ColumnFormats;
use Icinga\Module\Hcloud\Web\Reference;
use Icinga\Module\Hcloud\Web\ValueFormatter;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;

class AttributeList extends BaseHtmlElement
{
    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-attributes'];

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $labels
     * @param array<string, class-string> $enums
     * @param array<string, Reference> $references
     */
    public function __construct(
        private readonly array $values,
        private readonly array $labels,
        private readonly array $enums = [],
        private readonly ?string $table = null,
        private readonly array $references = []
    ) {
    }

    protected function assemble(): void
    {
        $table = new Table();
        $table->addAttributes(['class' => ['name-value-table', 'hcloud-attribute-table']]);

        foreach ($this->values as $column => $value) {
            $table->getBody()->add(Table::row([
                new HtmlElement(
                    'th',
                    Attributes::create([]),
                    Text::create($this->heading($column))
                ),
                $this->renderValue($column, $value),
            ]));
        }

        $this->addHtml($table);
    }

    private function heading(string $column): string
    {
        $reference = $this->references[$column] ?? null;
        if ($reference !== null) {
            return $reference->title;
        }

        return $this->labels[$column] ?? $column;
    }

    private function renderValue(string $column, mixed $value): ValidHtml
    {
        $reference = $this->references[$column] ?? null;
        if ($reference !== null) {
            return $reference->url === null
                ? Text::create($reference->label)
                : new Link($reference->label, $reference->url);
        }

        $format = ColumnFormats::for($this->table, $column);

        if ($format === ColumnFormat::Labels) {
            return new LabelList(ValueFormatter::labels(is_string($value) || is_array($value) ? $value : null));
        }

        $enum = $this->enums[$column] ?? null;
        if ($enum !== null && method_exists($enum, 'tryFromValue')) {
            $raw = is_string($value) ? $value : null;
            $case = $raw === null ? null : $enum::tryFromValue($raw);

            return new StatusIndicator($raw, $case instanceof HasLabel ? $case : null);
        }

        return Text::create($format->apply($value));
    }
}
