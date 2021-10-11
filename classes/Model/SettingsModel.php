<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Model;

/**
 * Class SettingsModel
 * @package QU\LERQ\Model
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class SettingsModel
{
	/** @var string */
	private $use_table = 'lerq_settings';

	/** @var string */
	private $use_index = 'keyword';

	/** @var \ilDBInterface  */
	private $database;

	/** @var SettingsItemModel[] */
	private $items;

	/**
	 * @param string $keyword
	 * @param mixed|null $value
	 * @return SettingsModel
	 */
	public function addItem(string $keyword, $value = null): SettingsModel
	{
		if (!array_key_exists($keyword, $this->items)) {
			$this->items[$keyword] = new SettingsItemModel($keyword, $value);
			$this->save($keyword);
		}
		return $this;
	}

	/**
	 * @param $keyword
	 * @return bool|SettingsItemModel
	 */
	public function getItem($keyword)
	{
		if (array_key_exists($keyword, $this->items)) {
			return $this->items[$keyword];
		}
		return new SettingsItemModel($keyword);
	}

	/**
	 * @return array
	 */
	public function getAll(): array
	{
		return $this->items;
	}

	/**
	 * @param $keyword
	 * @param $value
	 * @return SettingsModel
	 */
	public function __set($keyword, $value): SettingsModel
	{
		if (array_key_exists($keyword, $this->items)) {
			$this->items[$keyword]->setValue($value);
		}
		return $this;
	}

	/**
	 * @param $keyword
	 * @return mixed|null
	 */
	public function __get($keyword)
	{
		if (array_key_exists($keyword, $this->items)) {
			return $this->items[$keyword]->getValue();
		}
		return null;
	}

	/**
	 * @param string|null $keyword
	 * @return bool
	 */
	public function load(string $keyword = null): bool
	{
		if ($keyword !== null) {
			$data = $this->_loadBy($keyword);
		} else {
			$data = $this->_load();
		}
		if (!empty($data)) {
			foreach ($data as $rec) {
				$item = new SettingsItemModel($rec['keyword'], $rec['value']);
				$this->items[$rec['keyword']] = $item;
				unset($item);
			}
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function save($keyword = false): bool
	{
		if (empty($this->items)) {
			return false;
		}
		$ret = true;
		$fields = [
			'keyword',
			'value',
			'type'
		];
		$types = [
			'text',
			'text',
			'text'
		];
		if ($keyword) {
			$values = [
				$this->items[$keyword]->getKeyword(),
				$this->items[$keyword]->getValue(),
				'boolean'
			];
			return $this->_create($fields, $types, $values);
		}
		foreach ($this->items as $keyword => $item) {
			$values = [
				$item->getKeyword(),
				$item->getValue(),
				'boolean',
			];
			if (!$this->_update($fields, $types, $values, $keyword)) {
				$ret = false;
			}
		}
		return $ret;
	}

	/**
	 * @param string $keyword
	 * @return bool
	 */
	public function remove(string $keyword): bool
	{
		if (array_key_exists($keyword, $this->items)) {
			return $this->_delete($keyword);
		}
		return false;
	}

	/**
	 * SettingsModel constructor.
	 */
	public function __construct()
	{
		global $DIC;
		$this->database = $DIC->database();
		$this->items = [];
		$this->load();
	}

	/**
	 * Load all entries from database
	 *
	 * This is not recommended. You should use _loadById() instead.
	 *
	 * @return array			Array with database values like
	 * 							[ field_name => field_value ]
	 */
	final private function _load(): array
	{
		$select = 'SELECT * FROM `' . $this->use_table . '`;';

		$result = $this->database->query($select);

		$res = $this->database->fetchAll($result);
		return $res;
	}

	/**
	 * Load a specific entry be its ID
	 *
	 * This is the recommended function to load the data
	 * into your object. Just use this function inside
	 * your objects __construct() and assign the returned
	 * data to your objects parameters.
	 *
	 * @param int $id			Entry ID from $use_index field
	 * @return array			Array with database values like
	 * 							[ field_name => field_value ]
	 */
	final private function _loadBy(string $keyword): array
	{
		$select = 'SELECT * FROM `' . $this->use_table . '` WHERE ' . $this->use_index . ' = ' .
			$this->database->quote($keyword, 'text');

		$result = $this->database->query($select);

		$res = $this->database->fetchAll($result);
		return $res[0];
	}

	/**
	 * Create a new entry in database
	 *
	 * @param array $fields		Array of fields
	 * @param array $types		Array of field types
	 * @param array $values		Array of values to save
	 * @return bool
	 */
	final private function _create(array $fields, array $types, array $values)
	{
		$query = 'INSERT INTO `' . $this->use_table . '` ';
		$query .= '(' . implode(', ', $fields) . ') ';
		$query .= 'VALUES (' . implode(',', array_fill(0, count($fields), '%s')) . ') ';

		$res = $this->database->manipulateF(
			$query,
			$types,
			$values
		);

		return ($res === false);
	}

	/**
	 * Update an entry in database
	 *
	 * @param array $fields		Array of fields
	 * @param array $types		Array of field types
	 * @param array $values		Array of values to save
	 * @param int $whereIndex	Entry Keyword from $use_index field
	 * @return bool
	 */
	final private function _update(array $fields, array $types, array $values, string $whereIndex)
	{
		$query = 'UPDATE `' . $this->use_table . '` SET ';
		$query .= implode(' = %s,', $fields) . ' = %s ';
		$query .= 'WHERE ' . $this->use_index . ' = ' . $this->database->quote($whereIndex, 'text') . ';';

		$res = $this->database->manipulateF(
			$query,
			$types,
			$values
		);

		return ($res === false);
	}

	/**
	 * Delete an entry from database
	 *
	 * @param int $whereIndex	Entry Keyword from $use_index field
	 * @return bool
	 */
	final private function _delete(string $whereIndex)
	{

		$query = 'DELETE FROM `' . $this->use_table . '` WHERE ' . $this->use_index . ' = ' .
			$this->database->quote($whereIndex, 'text') . ';';

		$res = $this->database->manipulate($query);

		return ($res === false);
	}
}