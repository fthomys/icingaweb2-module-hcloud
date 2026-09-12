<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

use Icinga\Exception\NotFoundError;
use DateTimeImmutable;
use Icinga\Module\Hcloud\Metric\SeriesInfo;
use Icinga\Module\Hcloud\Metric\TimeRange;
use Icinga\Module\Hcloud\Sync\SyncOptions;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use Icinga\Module\Hcloud\Model\ModelIndex;
use Icinga\Module\Hcloud\Web\ReferenceResolver;
use Icinga\Module\Hcloud\Web\Widget\AttributeList;
use Icinga\Module\Hcloud\Web\Widget\MetricChart;
use Icinga\Module\Hcloud\Web\Widget\TimeRangePicker;
use Icinga\Module\Hcloud\Web\Widget\ResourceTable;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;
use PDO;
use ipl\Sql\Select;
use ipl\Web\Url;

abstract class DetailController extends Controller
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Child collections to show, as title => [table, foreign column, columns].
     *
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return [];
    }

    /**
     * A usage bar to show above the attributes, for resources with a known capacity.
     *
     * @param array<string, mixed> $values
     */
    protected function capacity(array $values, int $projectId): ?ValidHtml
    {
        return null;
    }

    /**
     * Resource type in hcloud_metric_sample, or null when this resource has no metrics.
     */
    protected function metricResourceType(): ?string
    {
        return null;
    }

    public function indexAction(): void
    {
        if (! $this->databaseIsReady()) {
            return;
        }

        $projectId = (int) $this->params->getRequired('project_id');
        $id = $this->params->getRequired('id');

        $class = $this->modelClass();
        $model = new $class();

        $query = $class::on($this->db())
            ->filter(Filter::equal('project_id', $projectId))
            ->filter(Filter::equal('id', $id));

        $row = $query->first();
        if ($row === null) {
            throw new NotFoundError($this->translate('No such object'));
        }

        $name = $row->name ?? $id;
        $this->setTitle(is_scalar($name) ? (string) $name : (string) $id);
        $this->addTitleTab($this->translate('Details'));

        $values = [];
        foreach ($model->getColumns() as $column) {
            if (is_string($column)) {
                $values[$column] = $row->$column ?? null;
            }
        }

        $capacity = $this->capacity($values, $projectId);
        if ($capacity !== null) {
            $this->addContent($capacity);
        }

        $this->addContent(new AttributeList(
            $values,
            $model->getColumnDefinitions(),
            $this->enumColumns(),
            $model->getTableName(),
            (new ReferenceResolver($this->db()))->forRow($values, $projectId)
        ));

        foreach ($this->relatedTables() as $title => $spec) {
            $this->addContent(new HtmlElement('h2', Attributes::create([]), Text::create($title)));
            $this->addContent($this->relatedTable($spec, $projectId, (string) $id));
        }

        $this->addMetrics($projectId, (int) $id);
    }

    private function addMetrics(int $projectId, int $resourceId): void
    {
        $resourceType = $this->metricResourceType();
        if ($resourceType === null) {
            return;
        }

        $range = TimeRange::tryFromValue($this->params->shift(TimeRange::PARAM)) ?? TimeRange::default();

        $now = new DateTimeImmutable('now');
        $start = $now->modify(sprintf('-%d hours', $range->hours()));

        $from = $start->getTimestamp();
        $to = $now->getTimestamp();
        $since = UtcDateTime::format($start);

        $select = (new Select())
            ->from('hcloud_metric_sample')
            ->columns(['series_name', 'ts', 'value'])
            ->where([
                'project_id = ?' => $projectId,
                'resource_type = ?' => $resourceType,
                'resource_id = ?' => $resourceId,
                'ts >= ?' => $since,
            ])
            ->orderBy(['series_name', 'ts']);

        $series = [];
        $oldest = null;
        $newest = null;

        foreach ($this->db()->yieldAll($select, PDO::FETCH_ASSOC) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = (string) ($row['series_name'] ?? '');
            if ($name === '') {
                continue;
            }

            $stamp = (string) ($row['ts'] ?? '');

            $series[$name][] = [
                'ts' => $stamp,
                'value' => isset($row['value']) ? (float) $row['value'] : null,
            ];

            if ($stamp !== '') {
                $oldest = $oldest === null ? $stamp : min($oldest, $stamp);
                $newest = $newest === null ? $stamp : max($newest, $stamp);
            }
        }

        // Drawing the whole requested window leaves dead space whenever the sync has not been
        // running for all of it. The buttons pick the widest window; the chart shows the part
        // of it that was actually measured.
        if ($oldest !== null && $newest !== null) {
            $measuredFrom = (int) strtotime($oldest . ' UTC');
            $measuredTo = (int) strtotime($newest . ' UTC');

            if ($measuredTo > $measuredFrom) {
                $from = max($from, $measuredFrom);
                $to = min($to, $measuredTo);
            }
        }

        $this->addContent(new HtmlElement(
            'h2',
            Attributes::create([]),
            Text::create($this->translate('Metrics'))
        ));

        $this->addContent(new TimeRangePicker(Url::fromRequest(), $range));

        if ($series === []) {
            $this->addContent(new HtmlElement(
                'p',
                Attributes::create(['class' => 'hcloud-metric-empty']),
                Text::create($this->translate('No samples in this range.'))
            ));

            return;
        }

        $groups = [];
        foreach (array_keys($series) as $name) {
            $info = SeriesInfo::forName($name);
            $groups[$info->group][] = $info;
        }

        uasort($groups, static function (array $a, array $b): int {
            return $a[0]->order <=> $b[0]->order;
        });

        foreach ($groups as $infos) {
            usort($infos, static fn (SeriesInfo $a, SeriesInfo $b): int => $a->order <=> $b->order);

            $samples = [];
            foreach ($infos as $info) {
                $samples[$info->name] = $series[$info->name];
            }

            $this->addContent(new MetricChart(
                self::groupTitle($infos),
                $infos,
                $samples,
                $infos[0]->unit,
                $from,
                $to,
                SyncOptions::fromConfig()->metricStep
            ));
        }
    }

    /**
     * A group holds the two halves of one measurement, such as read and write, so the shared
     * part of their labels makes the better heading.
     *
     * @param list<SeriesInfo> $infos
     */
    private static function groupTitle(array $infos): string
    {
        if (count($infos) === 1) {
            return $infos[0]->label;
        }

        $words = array_map(static fn (SeriesInfo $i): array => explode(' ', $i->label), $infos);
        $common = $words[0];

        foreach (array_slice($words, 1) as $other) {
            $shared = [];
            foreach ($common as $index => $word) {
                if (($other[$index] ?? null) === $word) {
                    $shared[] = $word;
                    continue;
                }

                break;
            }

            $common = $shared;
        }

        return $common === [] ? $infos[0]->label : implode(' ', $common);
    }

    /**
     * @param array{table: string, foreign: string, columns: list<string>} $spec
     */
    private function relatedTable(array $spec, int $projectId, string $id): ResourceTable
    {
        $select = (new Select())
            ->from($spec['table'])
            ->columns($spec['columns'])
            ->where([
                'project_id = ?' => $projectId,
                $spec['foreign'] . ' = ?' => $id,
            ]);

        $rows = [];
        foreach ($this->db()->yieldAll($select, PDO::FETCH_ASSOC) as $related) {
            if (is_array($related)) {
                $rows[] = $related;
            }
        }

        return new ResourceTable(
            $rows,
            $spec['columns'],
            ModelIndex::labelsFor($spec['table'], $spec['columns']),
            $this->enumColumns(),
            null,
            $spec['table']
        );
    }
}
