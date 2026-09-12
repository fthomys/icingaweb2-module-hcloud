<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Web\ValueFormatter;
use ipl\Sql\Select;

abstract class BaseImportSource extends ImportSourceHook
{
    /**
     * @return list<string>
     */
    abstract protected function columns(): array;

    abstract protected function table(): string;

    protected function keyColumn(): string
    {
        return 'name';
    }

    /**
     * @return list<string>
     */
    public function listColumns(): array
    {
        return array_merge(['project', 'labels_flat'], $this->columns());
    }

    /**
     * @return list<object>
     */
    public function fetchData(): array
    {
        $db = Database::get();

        $select = (new Select())
            ->from($this->table() . ' r')
            ->columns(array_merge(
                array_map(static fn (string $c): string => 'r.' . $c, $this->columns()),
                ['project' => 'p.name']
            ))
            ->joinLeft('hcloud_project p', 'p.id = r.project_id');

        $rows = [];
        foreach ($db->fetchAll($select) as $row) {
            $data = (array) $row;

            if (array_key_exists('labels', $data)) {
                $labels = ValueFormatter::labels(is_string($data['labels']) ? $data['labels'] : null);
                $data['labels_flat'] = implode(',', array_map(
                    static fn (string $k, string $v): string => $v === '' ? $k : $k . '=' . $v,
                    array_keys($labels),
                    array_values($labels)
                ));
            } else {
                $data['labels_flat'] = '';
            }

            $rows[] = (object) $data;
        }

        return $rows;
    }

    public static function getDefaultKeyColumnName(): string
    {
        return 'name';
    }

    public static function addSettingsFormFields(QuickForm $form): void
    {
        $form->addHtml(
            '<p>' . $form->translate(
                'This import source reads from the local hcloud database. Run "icingacli hcloud sync run" first.'
            ) . '</p>'
        );
    }
}
