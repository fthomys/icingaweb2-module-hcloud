<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Web\Widget\ResourceTable;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

abstract class ResourceController extends Controller
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    abstract protected function listTitle(): string;

    /**
     * @return list<string>
     */
    abstract protected function tableColumns(): array;

    /**
     * Columns offered by the sort control, as column => label.
     *
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        $model = $this->model();
        $definitions = $model->getColumnDefinitions();
        $columns = [];

        foreach ($model->getColumns() as $column) {
            if (is_string($column) && isset($definitions[$column])) {
                $columns[$column] = $definitions[$column];
            }
        }

        return $columns;
    }

    /**
     * Columns rendered through an enum, as column => enum class.
     *
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return [];
    }

    protected function detailUrl(): ?string
    {
        return null;
    }

    public function indexAction(): void
    {
        $this->setTitle($this->listTitle());

        if (! $this->databaseIsReady()) {
            return;
        }

        $query = $this->query();

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($query);
        $sortControl = $this->createSortControl($query, $this->sortColumns());
        $searchBar = $this->createSearchBar($query, [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->filterFromRequest();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();

                return;
            }
        } else {
            $filter = $this->filterFromRequest();
        }

        $query->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $detailUrl = $this->detailUrl();
        $keyName = (array) $this->model()->getKeyName();

        $linkFor = $detailUrl === null
            ? null
            : static function (object|array $row) use ($detailUrl, $keyName): ?Url {
                $params = [];
                foreach ($keyName as $key) {
                    $value = is_array($row) ? ($row[$key] ?? null) : ($row->$key ?? null);
                    if ($value === null) {
                        return null;
                    }

                    $params[$key] = $value;
                }

                return Url::fromPath($detailUrl, $params);
            };

        $this->addContent(new ResourceTable(
            $query,
            $this->tableColumns(),
            $this->model()->getColumnDefinitions(),
            $this->enumColumns(),
            $linkFor,
            $this->model()->getTableName()
        ));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction(): void
    {
        $suggestions = $this->createSearchBarSuggestions($this->query());

        $this->getDocument()->add($suggestions);
        $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor($this->query(), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    protected function model(): Model
    {
        static $model = null;

        if ($model === null) {
            $class = $this->modelClass();
            $model = new $class();
        }

        return $model;
    }

    protected function query(): Query
    {
        $class = $this->modelClass();
        $query = $class::on($this->db());

        if ($query->getResolver()->getRelations($query->getModel())->has('project')) {
            $query->with('project');
        }

        return $query;
    }

    protected function filterFromRequest(): Filter\Rule
    {
        return QueryString::parse((string) $this->params->toString());
    }

    protected function createSearchBarSuggestions(Query $query): HtmlElement
    {
        return new HtmlElement('ul', Attributes::create(['class' => 'search-suggestions']));
    }
}
