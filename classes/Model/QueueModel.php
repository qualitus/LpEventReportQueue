<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Model;

/**
 * Class QueueModel
 * @package QU\LERQ\Model
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class QueueModel
{
	/** @var int */
	private $id;
	/** @var string */
	private $timestamp;
	/** @var string („progress_changed“, „progress_reset“, etc.) */
	private $event;
	/** @var string ("lp_event", "member_event", "object_event") */
	private $event_type;
	/** @var string */
	private $progress;
	/** @var string */
	private $progress_changed;
	/** @var string */
	private $assignment;
	/** @var string */
	private $course_start;
	/** @var string */
	private $course_end;
	/** @var UserModel */
	private $user_data;
	/** @var ObjectModel */
	private $obj_data;
	/** @var MemberModel */
	private $mem_data;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return (isset($this->id) ? $this->id : -1);
	}

	/**
	 * @param int $id
	 * @return QueueModel
	 */
	public function setId($id): QueueModel
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param bool $iso		Get as ISO 8601 timestamp
	 * @return string|int|null
	 */
	public function getTimestamp($iso = false, $timezone = 'UTC')
	{
		if ($iso) {
			if ($timestamp = (isset($this->timestamp) ? $this->timestamp : false)) {
				$dt = new \DateTime();
				if (is_numeric($timestamp)) {
					$dt->setTimestamp($timestamp * 1);
				} else {
					$dt->setTimestamp(strtotime($timestamp));
				}
				$dt->setTimezone(new \DateTimeZone($timezone));
				return $dt->format('c');
			}
			return '';
		}
		return (isset($this->timestamp) ? $this->timestamp : NULL);
	}

	/**
	 * @param int|string $timestamp
	 * @return QueueModel
	 */
	public function setTimestamp($timestamp): QueueModel
	{
		if (is_numeric($timestamp)) {
			$this->timestamp = $timestamp*1;
		} else {
			$this->timestamp = strtotime($timestamp);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEvent(): string
	{
		return (isset($this->event) ? $this->event : '');
	}

	/**
	 * @param string $event
	 * @return QueueModel
	 */
	public function setEvent($event): QueueModel
	{
		$this->event = $event;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEventType(): string
	{
		return (isset($this->event_type) ? $this->event_type : '');
	}

	/**
	 * @param string $event_type
	 * @return QueueModel
	 */
	public function setEventType($event_type): QueueModel
	{
		$this->event_type = $event_type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getProgress(): string
	{
		return (isset($this->progress) ? $this->progress : '');
	}

	/**
	 * @param string $progress
	 * @return QueueModel
	 */
	public function setProgress($progress): QueueModel
	{
		$this->progress = $progress;
		return $this;
	}

    /**
     * @return string|int|null
     */
    public function getProgressChanged($iso = false)
    {
        if ($iso) {
            return (isset($this->progress_changed) ? date('c', $this->progress_changed) : '');
        }
        return (isset($this->progress_changed) ? $this->progress_changed : NULL);
    }

    /**
     * @param string $progress_changed
     * @return QueueModel
     */
    public function setProgressChanged($progress_changed): QueueModel
    {
        $this->progress_changed = $progress_changed;
        return $this;
    }

	/**
	 * @return string
	 */
	public function getAssignment(): string
	{
		return (isset($this->assignment) ? $this->assignment : '');
	}

	/**
	 * @param string $assignment
	 * @return QueueModel
	 */
	public function setAssignment($assignment): QueueModel
	{
		$this->assignment = $assignment;
		return $this;
	}

	/**
	 * @param bool $iso		Get as ISO 8601 timestamp
	 * @return string|int|null
	 */
	public function getCourseStart($iso = false)
	{
		if ($iso) {
			return (isset($this->course_start) ? date('c', $this->course_start) : '');
		}
		return (isset($this->course_start) ? $this->course_start : NULL);
	}

	/**
	 * @param string $course_start
	 * @return QueueModel
	 */
	public function setCourseStart($course_start): QueueModel
	{
		$this->course_start = $course_start;
		return $this;
	}

	/**
	 * @param bool $iso		Get as ISO 8601 timestamp
	 * @return string|int|null
	 */
	public function getCourseEnd($iso = false)
	{
		if ($iso) {
			return (isset($this->course_end) ? date('c', $this->course_end) : '');
		}
		return (isset($this->course_end) ? $this->course_end : NULL);
	}

	/**
	 * @param string $course_end
	 * @return QueueModel
	 */
	public function setCourseEnd($course_end): QueueModel
	{
		$this->course_end = $course_end;
		return $this;
	}

	/**
	 * @return UserModel
	 */
	public function getUserData(): UserModel
	{
		return $this->user_data;
	}

	/**
	 * @param UserModel $user_data
	 * @return QueueModel
	 */
	public function setUserData(UserModel $user_data): QueueModel
	{
		$this->user_data = $user_data;
		return $this;
	}

	/**
	 * @return ObjectModel
	 */
	public function getObjData(): ObjectModel
	{
		return $this->obj_data;
	}

	/**
	 * @param ObjectModel $obj_data
	 * @return QueueModel
	 */
	public function setObjData(ObjectModel $obj_data): QueueModel
	{
		$this->obj_data = $obj_data;
		return $this;
	}

	/**
	 * @return MemberModel
	 */
	public function getMemData(): MemberModel
	{
		return $this->mem_data;
	}

	/**
	 * @param MemberModel $mem_data
	 * @return QueueModel
	 */
	public function setMemData(MemberModel $mem_data): QueueModel
	{
		$this->mem_data = $mem_data;
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return json_encode([
			'id' => $this->getId(),
			'timestamp' => $this->getTimestamp(true),
			'event' => $this->getEvent(),
			'event_type' => $this->getEventType(),
			'progress' => $this->getProgress(),
			'progress_changed' => $this->getProgressChanged(),
			'assignment' => $this->getAssignment(),
			'course_start' => $this->getCourseStart(),
			'course_end' => $this->getCourseEnd(),
			'user_data' => json_decode($this->getUserData()),
			'obj_data' => json_decode($this->getObjData()),
			'mem_data' => json_decode($this->getMemData()),
		]);
	}


}