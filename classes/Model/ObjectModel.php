<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Model;

/**
 * Class ObjectModel
 * @package QU\LERQ\Model
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class ObjectModel
{
	/** @var string */
	private $title;
	/** @var int */
	private $id;
	/** @var int */
	private $ref_id;
	/** @var string */
	private $link;
	/** @var string */
	private $type;
	/** @var string */
	private $type_hr;
	/** @var string */
	private $course_title;
	/** @var int */
	private $course_id;
	/** @var int */
	private $course_ref_id;

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return (isset($this->title) ? $this->title : '');
	}

	/**
	 * @param string $title
	 * @return ObjectModel
	 */
	public function setTitle($title): ObjectModel
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return (isset($this->id) ? $this->id : -1);
	}

	/**
	 * @param int $id
	 * @return ObjectModel
	 */
	public function setId($id): ObjectModel
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getRefId(): int
	{
		return (isset($this->ref_id) ? $this->ref_id : -1);
	}

	/**
	 * @param int $ref_id
	 * @return ObjectModel
	 */
	public function setRefId($ref_id): ObjectModel
	{
		$this->ref_id = $ref_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLink(): string
	{
		return (isset($this->link) ? $this->link : '');
	}

	/**
	 * @param string $link
	 * @return ObjectModel
	 */
	public function setLink($link): ObjectModel
	{
		$this->link = $link;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return (isset($this->type) ? $this->type : '');
	}

	/**
	 * @param string $type
	 * @return ObjectModel
	 */
	public function setType($type): ObjectModel
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTypeHr(): string
	{
		return (isset($this->type_hr) ? $this->type_hr : $this->translateType());
	}

	/**
	 * @param string $type_hr
	 * @return ObjectModel
	 */
	public function setTypeHr($type_hr): ObjectModel
	{
		$this->type_hr = $type_hr;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCourseTitle(): string
	{
		return (isset($this->course_title) ? $this->course_title : '');
	}

	/**
	 * @param string $course_title
	 * @return ObjectModel
	 */
	public function setCourseTitle($course_title): ObjectModel
	{
		$this->course_title = $course_title;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCourseId(): int
	{
		return (isset($this->course_id) ? $this->course_id : -1);
	}

	/**
	 * @param int $course_id
	 * @return ObjectModel
	 */
	public function setCourseId($course_id): ObjectModel
	{
		$this->course_id = $course_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCourseRefId(): int
	{
		return (isset($this->course_ref_id) ? $this->course_ref_id : -1);
	}

	/**
	 * @param int $course_ref_id
	 * @return ObjectModel
	 */
	public function setCourseRefId($course_ref_id): ObjectModel
	{
		$this->course_ref_id = $course_ref_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function translateType(): string
	{
		if ($this->getType() !== '') {
			switch ($this->getType()) {
				case 'adm':
					return 'SystemFolder';
					break;
				case 'assf':
					return 'AssessentFolder';
					break;
				case 'bibl':
					return 'Bibliographic';
					break;
				case 'blog':
					return 'Blog';
					break;
				case 'book':
					return 'BookingPool';
					break;
				case 'cat':
					return 'Category';
					break;
				case 'catr':
					return 'CategoryReference';
					break;
				case 'crs':
					return 'Course';
					break;
				case 'crsr':
					return 'CourseReference';
					break;
				case 'dcl':
					return 'DataCollection';
					break;
				case 'exc':
					return 'Excercise';
					break;
				case 'fold':
					return 'Folder';
					break;
				case 'frm':
					return 'Forum';
					break;
				case 'glo':
					return 'Glossary';
					break;
				case 'grp':
					return 'Group';
					break;
				case 'grpr':
					return 'GroupReference';
					break;
				case 'iass':
					return 'IndividualAssessment';
					break;
				case 'lm':
					return 'LearningModule';
					break;
				case 'prg':
					return 'StudyProgramme';
					break;
				case 'role':
					return 'Role';
					break;
				case 'rolf':
					return 'RoleFolder';
					break;
				case 'sahs':
					return 'SAHSLearningModule';
					break;
				case 'sess':
					return 'Session';
					break;
				case 'trac':
					return 'UserTracking';
					break;
				case 'tst':
					return 'Test';
					break;
				case 'usr':
					return 'User';
					break;
			}
		}
		return '';
	}

	/**
	 * @return false|string
	 */
	public function __toString()
	{
		return json_encode([
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'ref_id' => $this->getRefId(),
			'link' => $this->getLink(),
			'type' => $this->getType(),
			'type_hr' => $this->getTypeHr(),
			'course_title' => $this->getCourseTitle(),
			'course_id' => $this->getCourseId(),
			'course_ref_id' => $this->getCourseRefId(),
		]);
	}
}