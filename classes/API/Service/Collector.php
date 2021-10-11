<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\API\Service;

use QU\LERQ\Collections\QueueCollection;
use QU\LERQ\Model\MemberModel;
use QU\LERQ\Model\ObjectModel;
use QU\LERQ\Model\QueueModel;
use QU\LERQ\Model\UserModel;

/**
 * Class Collector
 * @package QU\LERQ\Queue
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class Collector
{
	/** @var \QU\LERQ\API\Filter\FilterObject  */
	private $filter;
	/** @var \ilDBInterface  */
	private $database;

	/**
	 * Collector constructor.
	 * @param \QU\LERQ\API\Filter\FilterObject $filter
	 */
	public function __construct(\QU\LERQ\API\Filter\FilterObject $filter)
	{
		global $DIC;

		$this->filter = $filter;
		$this->database = $DIC->database();
	}

	/**
	 * @param bool $no_convert
	 * @return QueueCollection
	 */
	public function collect(bool $no_convert = false): QueueCollection
	{
		$collection = new QueueCollection();
		$items = [];

		$query = $this->createSelect();
		$query .= $this->createWhereByFilter();

		$this->queryQueue($query, $items);
		if ($no_convert !== true) {
			$items = $this->buildModels($items);
		}

		return $collection->create($items);
	}

	/**
	 * @return string
	 */
	private function createSelect()
	{
		$select = 'SELECT ';
		$select .= '`id`, `timestamp`, `event`, `event_type`, `progress`, `assignment`, ';
		$select .= '`course_start`, `course_end`, `user_data`, `obj_data`, `mem_data`, `progress_changed` ';
		$select .= 'FROM `lerq_queue` ';

		return $select;
	}

	/**
	 * @return string
	 */
	private function createWhereByFilter(): string
	{
		$db = $this->database;
		$where = '';
		$limit = '';
		$order = '';

		/* Time based filter */
		if ($this->filter->getCourseStart() !== false) {
			if ($this->filter->getCourseStartDirection() === $this->filter::TIME_BEFORE) {
				$where .= '' . $db->quoteIdentifier('course_start') . ' <= ' .
					$db->quote($this->filter->getCourseStart(), 'timestamp');
			} else {
				$where .= '' . $db->quoteIdentifier('course_start') . ' >= ' .
					$db->quote($this->filter->getCourseStart(), 'timestamp');
			}
			$where .= ' AND ';
		}
		if ($this->filter->getCourseEnd() !== false) {
			if ($this->filter->getCourseEndDirection() === $this->filter::TIME_AFTER) {
				$where .= '' . $db->quoteIdentifier('course_end') . ' >= ' .
					$db->quote($this->filter->getCourseEnd(), 'timestamp');
			} else {
				$where .= '' . $db->quoteIdentifier('course_end') . ' <= ' .
					$db->quote($this->filter->getCourseEnd(), 'timestamp');
			}
			$where .= ' AND ';
		}
		if (!$this->filter->getEventHappenedStart() && !$this->filter->getEventHappenedEnd()) {
			if ($this->filter->getEventHappened() !== false) {
				if ($this->filter->getEventHappenedDirection() === $this->filter::TIME_BEFORE) {
					$where .= '' . $db->quoteIdentifier('timestamp') . ' <= ' .
						$db->quote($this->filter->getEventHappened(), 'timestamp');
				} else {
					$where .= '' . $db->quoteIdentifier('timestamp') . ' >= ' .
						$db->quote($this->filter->getEventHappened(), 'timestamp');
				}
				$where .= ' AND ';
			}
		}
		if($this->filter->getEventHappenedStart()){
			$where .= '' . $db->quoteIdentifier('timestamp') . ' <= ' .
				$db->quote($this->filter->getEventHappenedStart(), 'timestamp') . ' AND ';
		}
		if($this->filter->getEventHappenedEnd()) {
			$where .= '' . $db->quoteIdentifier('timestamp') . ' >= ' .
				$db->quote($this->filter->getEventHappenedEnd(), 'timestamp') . ' AND ';
		}
        if ($this->filter->getProgressChanged() !== false) {
            if ($this->filter->getProgressChangedDirection() === $this->filter::TIME_BEFORE) {
                $where .= '' . $db->quoteIdentifier('progress_changed') . ' <= ' .
                    $db->quote($this->filter->getProgressChanged(), 'timestamp');
            } else {
                $where .= '' . $db->quoteIdentifier('progress_changed') . ' >= ' .
                    $db->quote($this->filter->getProgressChanged(), 'timestamp');
            }
            $where .= ' AND ';
        }

		/* Event related filter */
		if ($this->filter->getProgress() !== '*') {
			$where .= '' . $db->quoteIdentifier('progress') . ' = ' .
				$db->quote($this->filter->getProgress(), 'text') . ' ';
			$where .= ' AND ';
		}
		if ($this->filter->getAssignment() !== '*') {
			$where .= '' . $db->quoteIdentifier('assignment') . ' = ' .
				$db->quote($this->filter->getAssignment(), 'text') . ' ';
			$where .= ' AND ';
		}

		/* Event type filter */
		if ($this->filter->getEventType() !== '*') {
			$where .= '' . $db->quoteIdentifier('event_type') . ' = ' .
				$db->quote($this->filter->getEventType(), 'text') . ' AND ';
		}

		/* simple filter */
		if ($this->filter->getEvent() !== '*') {
			$where .= '' . $db->quoteIdentifier('event') . ' = ' .
				$db->quote($this->filter->getEvent(), 'text') . ' ';
			$where .= ' AND ';
		}

		/* Paging filter */
		if ($this->filter->getPageStart() !== 0) {
			if ($this->filter->isNegativePager()) {
				$where .= '' . $db->quoteIdentifier('id') . ' < ' .
					$db->quote($this->filter->getPageStart(), 'integer') . ' ';
			} else {
				$where .= '' . $db->quoteIdentifier('id') . ' > ' .
					$db->quote($this->filter->getPageStart(), 'integer') . ' ';
			}
			$where .= ' AND ';
		}

		if ($this->filter->getPageLength() !== -1) {
			$limit .= ' LIMIT ' . $db->quote($this->filter->getPageLength(), 'integer') . ' ';
		}

		if ($this->filter->isNegativePager()) {
			$order .= ' ORDER BY ' . $db->quoteIdentifier('id') . ' DESC ';
		} else {
			$order .= ' ORDER BY ' . $db->quoteIdentifier('id') . ' ASC ';
		}

		if (strlen($where) > 0) {
			$where = ' WHERE ' . $where . ' TRUE ' . $order . $limit;

		} else {
			$where = $order . $limit;
		}

		return $where;
	}

	/**
	 * @param string $query
	 * @param array $items
	 * @return void
	 */
	private function queryQueue(string $query, array &$items = [])
	{
		$res = $this->database->query($query);

		while ($row = $this->database->fetchAssoc($res)) {
			$items[] = $row;
		}
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private function buildModels(array $items)
	{
		$models = [];
		if (empty($items)) {
			return $models;
		} // @todo check if model data is given
		foreach ($items as $item) {
			$qm = new QueueModel();
			$qm->setId($item['id'])
				->setTimestamp($item['timestamp'])
				->setEvent($item['event'])
				->setEventType($item['event_type'])
				->setProgress($item['progress'])
				->setAssignment($item['assignment'])
				->setCourseStart($item['course_start'])
				->setCourseEnd($item['course_end']);

			$item_ud = json_decode($item['user_data'], true);
			$um = new UserModel();
			$um->setUsrId($item_ud['usr_id'])
				->setLogin($item_ud['username'])
				->setFirstname($item_ud['firstname'])
				->setLastname($item_ud['lastname'])
				->setTitle($item_ud['title'])
				->setGender($item_ud['gender'])
				->setEmail($item_ud['email'])
				->setInstitution($item_ud['institution'])
				->setStreet($item_ud['street'])
				->setCity($item_ud['city'])
				->setCountry($item_ud['country'])
				->setPhoneOffice($item_ud['phone_office'])
				->setHobby($item_ud['hobby'])
				->setPhoneHome($item_ud['phone_home'])
				->setPhoneMobile($item_ud['phone_mobile'])
				->setFax($item_ud['phone_fax'])
				->setReferralComment($item_ud['referral_comment'])
				->setMatriculation($item_ud['matriculation'])
				->setActive($item_ud['active'])
				->setApprovalDate($item_ud['approval_date'])
				->setAgreeDate($item_ud['agree_date'])
				->setAuthMode($item_ud['auth_mode'])
				->setExtAccount($item_ud['ext_account'])
				->setBirthday($item_ud['birthday'])
				->setImportId($item_ud['import_id'])
				->setUdfData($item_ud['udf_data']);
			$qm->setUserData($um);
			unset($item_ud);
			unset($um);

			$item_om = json_decode($item['obj_data'], true);
			$om = new ObjectModel();
			$om->setTitle($item_om['title'])
				->setId($item_om['id'])
				->setRefId($item_om['ref_id'])
				->setLink($item_om['link'])
				->setType($item_om['type'])
				->setCourseTitle($item_om['course_title'])
				->setCourseId($item_om['course_id'])
				->setCourseRefId($item_om['course_ref_id']);
			$qm->setObjData($om);
			unset($item_om);
			unset($om);

			$item_mm = json_decode($item['mem_data'], true);
			$mm = new MemberModel();
			$mm->setMemberRole($item_mm['role'])
				->setCourseTitle($item_mm['course_title'])
				->setCourseId($item_mm['course_id'])
				->setCourseRefId($item_mm['course_ref_id']);
			$qm->setMemData($mm);
			unset($item_mm);
			unset($mm);

			$models[$item['id']] = $qm;
			unset($qm);
		}

		return $models;
	}
}