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

namespace QU\LERQ\Model;

use ilDBConstants;
use ilDBInterface;

class SettingsModel
{
    private string $use_table = 'lerq_settings';
    private string $use_index = 'keyword';
    private ilDBInterface $database;
    /** @var array<string, SettingsItemModel> */
    private array $items = [];

    public function __construct()
    {
        global $DIC;

        $this->database = $DIC->database();
        $this->load();
    }

    /**
     * @param mixed|null $value
     */
    public function addItem(string $keyword, $value = null): self
    {
        if (!array_key_exists($keyword, $this->items)) {
            $this->items[$keyword] = new SettingsItemModel($keyword, $value);
            $this->save($keyword);
        }

        return $this;
    }

    public function getItem(string $keyword): SettingsItemModel
    {
        if (array_key_exists($keyword, $this->items)) {
            return $this->items[$keyword];
        }

        return new SettingsItemModel($keyword);
    }

    /**
     * @return array<string, SettingsItemModel>
     */
    public function getAll(): array
    {
        return $this->items;
    }

    /**
     * @param mixed|null $value
     */
    public function __set(string $keyword, $value): void
    {
        if (array_key_exists($keyword, $this->items)) {
            $this->items[$keyword]->setValue($value);
        }
    }

    public function __isset(string $keyword): bool
    {
        return isset($this->items[$keyword]) || array_key_exists($keyword, $this->items);
    }

    /**
     * @return mixed|null
     */
    public function __get(string $keyword)
    {
        if (array_key_exists($keyword, $this->items)) {
            return $this->items[$keyword]->getValue();
        }

        return null;
    }

    private function load(): void
    {
        $data = $this->_load();

        if (!empty($data)) {
            foreach ($data as $rec) {
                $item = new SettingsItemModel($rec['keyword'], $rec['value']);
                $this->items[$rec['keyword']] = $item;
            }
        }
    }

    public function save($keyword = false): void
    {
        if ($this->items === []) {
            return;
        }

        $ret = true;

        $fields = [
            'keyword',
            'value',
            'type'
        ];
        $types = [
            ilDBConstants::T_TEXT,
            ilDBConstants::T_TEXT,
            ilDBConstants::T_TEXT
        ];

        if ($keyword) {
            $values = [
                $this->items[$keyword]->getKeyword(),
                $this->items[$keyword]->getValue(),
                'boolean'
            ];

            $this->_create($fields, $types, $values);
            return;
        }

        foreach ($this->items as $key => $item) {
            $values = [
                $item->getKeyword(),
                $item->getValue(),
                'boolean',
            ];

            $this->_update($fields, $types, $values, $key);
        }
    }

    public function remove(string $keyword): bool
    {
        if (array_key_exists($keyword, $this->items)) {
            return $this->_delete($keyword);
        }

        return false;
    }

    /**
     * Load all entries from database
     * This is not recommended. You should use _loadById() instead.
     * @return list<array<string, mixed>
     */
    private function _load(): array
    {
        $select = 'SELECT * FROM `' . $this->use_table . '`;';

        $result = $this->database->query($select);

        return $this->database->fetchAll($result);
    }

    /**
     * Create a new entry in database
     * @param list<string> $fields Array of fields
     * @param list<string> $types Array of field types
     * @param list<scalar> $values Array of values to save
     */
    private function _create(array $fields, array $types, array $values): bool
    {
        $query = 'INSERT INTO `' . $this->use_table . '` ';
        $query .= '(' . implode(', ', $fields) . ') ';
        $query .= 'VALUES (' . implode(',', array_fill(0, count($fields), '%s')) . ') ';

        $affected_rows = $this->database->manipulateF(
            $query,
            $types,
            $values
        );

        return $affected_rows > 0;
    }

    /**
     * Update an entry in database
     * @param list<string> $fields Array of fields
     * @param list<string> $types Array of field types
     * @param list<scalar> $values Array of values to save
     */
    private function _update(array $fields, array $types, array $values, string $whereIndex): bool
    {
        $query = 'UPDATE `' . $this->use_table . '` SET ';
        $query .= implode(' = %s,', $fields) . ' = %s ';
        $query .= 'WHERE ' . $this->use_index . ' = ' . $this->database->quote($whereIndex, ilDBConstants::T_TEXT) . ';';

        $affected_rows = $this->database->manipulateF(
            $query,
            $types,
            $values
        );

        return $affected_rows > 0;
    }

    private function _delete(string $whereIndex): bool
    {
        $query = 'DELETE FROM `' . $this->use_table . '` WHERE ' . $this->use_index . ' = ' .
            $this->database->quote($whereIndex, ilDBConstants::T_TEXT) . ';';

        $affected_rows = $this->database->manipulate($query);

        return $affected_rows > 0;
    }
}
