<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Enum\HasLabel;
use Icinga\Module\Hcloud\Web\ColumnFormat;
use Icinga\Module\Hcloud\Web\ColumnFormats;
use Icinga\Module\Hcloud\Web\ValueFormatter;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;
use ipl\Web\Url;

class ResourceTable extends Table
{
    /** @var array<string, mixed> */
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    /**
     * @param iterable<object|array<string, mixed>> $rows
     * @param list<string> $columns
     * @param array<string, string> $labels
     * @param array<string, class-string> $enums
     * @param (callable(object|array<string, mixed>): ?Url)|null $linkFor
     */
    public function __construct(
        private readonly iterable $rows,
        private readonly array $columns,
        private readonly array $labels,
        private readonly array $enums = [],
        private $linkFor = null,
        private readonly ?string $table = null
    ) {
    }

    protected function assemble(): void
    {
        $header = [];
        foreach ($this->columns as $column) {
            $header[] = $this->labels[$column] ?? $column;
        }

        $this->getHeader()->add(Table::row($header, null, 'th'));

        $empty = true;
        foreach ($this->rows as $row) {
            $empty = false;
            $cells = [];

            foreach ($this->columns as $index => $column) {
                $cells[] = $this->cell($row, $column, $index === 0);
            }

            $this->getBody()->add(Table::row($cells));
        }

        if ($empty) {
            $this->getBody()->add(Table::row([
                new HtmlElement(
                    'td',
                    Attributes::create(['colspan' => (string) (count($this->columns) ?: 1), 'class' => 'empty-state']),
                    Text::create(mt('hcloud', 'No results found.'))
                ),
            ]));
        }
    }

    /**
     * @param object|array<string, mixed> $row
     */
    private function cell(object|array $row, string $column, bool $isFirst): ValidHtml
    {
        $value = self::value($row, $column);

        if (ColumnFormats::for($this->table, $column) === ColumnFormat::Labels) {
            return new LabelList(ValueFormatter::labels(is_string($value) || is_array($value) ? $value : null));
        }

        if (isset($this->enums[$column])) {
            return new StatusIndicator(
                is_string($value) ? $value : null,
                $this->enumFor($column, is_string($value) ? $value : null)
            );
        }

        $text = $this->format($column, $value);

        if ($isFirst && $this->linkFor !== null) {
            $url = ($this->linkFor)($row);
            if ($url !== null) {
                return new Link($text, $url);
            }
        }

        return Text::create($text);
    }

    /**
     * ipl\Orm hands us hydrated models, while a raw ipl\Sql read hands us associative arrays.
     *
     * @param object|array<string, mixed> $row
     */
    private static function value(object|array $row, string $column): mixed
    {
        if (is_array($row)) {
            return $row[$column] ?? null;
        }

        return $row->$column ?? null;
    }

    private function enumFor(string $column, ?string $value): ?HasLabel
    {
        $enum = $this->enums[$column] ?? null;
        if ($enum === null || $value === null || ! method_exists($enum, 'tryFromValue')) {
            return null;
        }

        $case = $enum::tryFromValue($value);

        return $case instanceof HasLabel ? $case : null;
    }

    private function format(string $column, mixed $value): string
    {
        return ColumnFormats::for($this->table, $column)->apply($value);
    }
}
