<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Model;

/**
 * Class SettingsItemModel
 * @package QU\LERQ\Model
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class SettingsItemModel
{
	/** @var string */
	protected $keyword = '';

	/** @var string */
	protected $type;

	/** @var mixed */
	protected $value = null;

	/**
	 * SettingsItemModel constructor.
	 * @param string $keyword
	 * @param null $value
	 */
	public function __construct(string $keyword, $value = null)
	{
		$this->keyword = $keyword;
		if (isset($value)) {
			$this->setValue($value);
		}
	}

	/**
	 * @return mixed
	 */
	public function getKeyword()
	{
		return $this->keyword;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return SettingsItemModel
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

}