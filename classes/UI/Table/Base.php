<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace QU\LERQ\UI\Table;

use QU\LERQ\UI\Table\Data\Provider;
use ilTable2GUI;

/**
 *  @template T
 */
abstract class Base extends ilTable2GUI
{
    protected ?Provider $provider = null;
    /** @var array<string, string> */
    protected array $visibleOptionalColumns = [];
    /** @var array<string, array{'field': string, 'txt': string, 'default': bool, "optional"?: bool, "sortable": bool, "is_checkbox"?: bool, "width"?: string}> */
    protected array $optionalColumns = [];
    /** @var array<string, mixed> */
    protected array $filter = [];
    /** @var array<string, mixed> */
    protected array $optional_filter = [];

    public function __construct(object $a_parent_obj, string $command = '')
    {
        parent::__construct($a_parent_obj, $command, '');

        $columns = $this->getColumnDefinition();
        $this->optionalColumns = $this->getSelectableColumns();
        $this->visibleOptionalColumns = $this->getSelectedColumns();

        foreach ($columns as $index => $column) {
            if ($this->isColumnVisible($index)) {
                $this->addColumn(
                    $column['txt'],
                    isset($column['sortable']) && $column['sortable'] ? $column['field'] : '',
                    $column['width'] ?? '',
                    isset($column['is_checkbox']) && (bool) $column['is_checkbox']
                );
            }
        }
    }

    public function withProvider(Provider $provider): self
    {
        $clone = clone $this;
        $clone->provider = $provider;

        return $clone;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    /**
     * This method can be used to add parameters or filter values passed to the provider
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filter
     */
    protected function onBeforeDataFetched(array &$params, array &$filter): void
    {
    }

    /**
     * This method can be used to add some field values dynamically or manipulate existing values of the table row array
     * @param array<string, mixed> $row
     */
    protected function prepareRow(array &$row): void
    {
    }

    /**
     * @param array{items: list<T>, cnt: int} $data
     */
    protected function preProcessData(array &$data): void
    {
    }

    /**
     * Define a final formatting for a cell value
     * @param array<string, mixed> $row
     */
    protected function formatCellValue(string $column, array $row): string
    {
        if (is_scalar($row[$column])) {
            return trim((string) $row[$column]);
        }

        return '';
    }

    /**
     * @return array<string, array{'field': string, 'txt': string, 'default': bool, "optional"?: bool, "sortable": bool, "is_checkbox"?: bool, "width"?: string}>
     */
    public function getSelectableColumns(): array
    {
        $optionalColumns = array_filter($this->getColumnDefinition(), static function (array $column): bool {
            return isset($column['optional']) && $column['optional'];
        });

        $columns = [];
        foreach ($optionalColumns as $index => $column) {
            $columns[$column['field']] = $column;
        }

        return $columns;
    }

    protected function isColumnVisible(int $index): bool
    {
        $columnDefinition = $this->getColumnDefinition();
        if (array_key_exists($index, $columnDefinition)) {
            $column = $columnDefinition[$index];
            if (isset($column['optional']) && !$column['optional']) {
                return true;
            }

            if (is_array($this->visibleOptionalColumns) &&
                array_key_exists($column['field'], $this->visibleOptionalColumns)) {
                return true;
            }
        }

        return false;
    }

    final protected function fillRow(array $a_set): void
    {
        $this->prepareRow($a_set);

        foreach ($this->getColumnDefinition() as $index => $column) {
            if (!$this->isColumnVisible($index)) {
                continue;
            }

            $this->tpl->setCurrentBlock('column');
            $value = $this->formatCellValue($column['field'], $a_set);
            if ($value === '') {
                $this->tpl->touchBlock('column');
            } else {
                $this->tpl->setVariable('COLUMN_VALUE', $value);
            }

            $this->tpl->parseCurrentBlock();
        }
    }

    /**
     * @return non-empty-list<array{'field': string, 'txt': string, 'default': bool, "optional"?: bool, "sortable": bool, "is_checkbox"?: bool, "width"?: string}>
     */
    abstract protected function getColumnDefinition(): array;

    public function populate(): self
    {
        if ($this->getExternalSegmentation() && $this->getExternalSorting()) {
            $this->determineOffsetAndOrder();
        } elseif (!$this->getExternalSegmentation() && $this->getExternalSorting()) {
            $this->determineOffsetAndOrder(true);
        }

        $params = [];
        if ($this->getExternalSegmentation()) {
            $params['limit'] = $this->getLimit();
            $params['offset'] = $this->getOffset();
        }
        if ($this->getExternalSorting()) {
            $params['order_field'] = $this->getOrderField();
            $params['order_direction'] = $this->getOrderDirection();
        }

        $this->determineSelectedFilters();
        $filter = $this->filter;

        foreach ($this->optional_filter as $key => $value) {
            if ($this->isFilterSelected($key)) {
                $filter[$key] = $value;
            }
        }

        $this->onBeforeDataFetched($params, $filter);
        $data = $this->getProvider()->getList($params, $filter);

        if (!count($data['items']) && $this->getOffset() > 0 && $this->getExternalSegmentation()) {
            $this->resetOffset();
            if ($this->getExternalSegmentation()) {
                $params['limit'] = $this->getLimit();
                $params['offset'] = $this->getOffset();
            }
            $data = $this->provider->getList($params, $filter);
        }

        $this->preProcessData($data);

        $this->setData($data['items']);
        if ($this->getExternalSegmentation()) {
            $this->setMaxCount($data['cnt'] ?? 0);
        }

        return $this;
    }
}
