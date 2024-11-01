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

namespace QU\LERQ\API\Service;

use ilDBConstants;
use ilDBInterface;
use QU\LERQ\Collections\QueueCollection;
use QU\LERQ\Model\MemberModel;
use QU\LERQ\Model\ObjectModel;
use QU\LERQ\Model\QueueModel;
use QU\LERQ\Model\UserModel;

class Collector
{
    private \QU\LERQ\API\Filter\FilterObject $filter;
    private ilDBInterface $database;

    public function __construct(\QU\LERQ\API\Filter\FilterObject $filter)
    {
        global $DIC;

        $this->filter = $filter;
        $this->database = $DIC->database();
    }

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

    private function createSelect(): string
    {
        $select = 'SELECT ';
        $select .= '`id`, `timestamp`, `event`, `event_type`, `progress`, `assignment`, ';
        $select .= '`course_start`, `course_end`, `user_data`, `obj_data`, `mem_data`, `progress_changed` ';
        $select .= 'FROM `lerq_queue` ';

        return $select;
    }

    private function createWhereByFilter(): string
    {
        $db = $this->database;
        $where = '';
        $limit = '';
        $order = '';

        /* Time based filter */
        if ($this->filter->getCourseStart() !== null) {
            if ($this->filter->getCourseStartDirection() === $this->filter::TIME_BEFORE) {
                $where .= $db->quoteIdentifier('course_start') . ' <= ' .
                    $db->quote($this->filter->getCourseStart(), ilDBConstants::T_INTEGER);
            } else {
                $where .= $db->quoteIdentifier('course_start') . ' >= ' .
                    $db->quote($this->filter->getCourseStart(), ilDBConstants::T_INTEGER);
            }
            $where .= ' AND ';
        }
        if ($this->filter->getCourseEnd() !== null) {
            if ($this->filter->getCourseEndDirection() === $this->filter::TIME_AFTER) {
                $where .= $db->quoteIdentifier('course_end') . ' >= ' .
                    $db->quote($this->filter->getCourseEnd(), ilDBConstants::T_INTEGER);
            } else {
                $where .= $db->quoteIdentifier('course_end') . ' <= ' .
                    $db->quote($this->filter->getCourseEnd(), ilDBConstants::T_INTEGER);
            }
            $where .= ' AND ';
        }

        if ($this->filter->getEventHappenedStart() === null && $this->filter->getEventHappenedEnd() === null) {
            if ($this->filter->getEventHappened() !== null) {
                if ($this->filter->getEventHappenedDirection() === $this->filter::TIME_BEFORE) {
                    $where .= $db->quoteIdentifier('timestamp') . ' <= ' .
                        $db->quote($this->filter->getEventHappened(), ilDBConstants::T_INTEGER);
                } else {
                    $where .= $db->quoteIdentifier('timestamp') . ' >= ' .
                        $db->quote($this->filter->getEventHappened(), ilDBConstants::T_INTEGER);
                }
                $where .= ' AND ';
            }
        }
        if ($this->filter->getEventHappenedStart() !== null) {
            $where .= $db->quoteIdentifier('timestamp') . ' <= ' .
                $db->quote($this->filter->getEventHappenedStart(), ilDBConstants::T_INTEGER) . ' AND ';
        }
        if ($this->filter->getEventHappenedEnd() !== null) {
            $where .= $db->quoteIdentifier('timestamp') . ' >= ' .
                $db->quote($this->filter->getEventHappenedEnd(), ilDBConstants::T_INTEGER) . ' AND ';
        }

        if ($this->filter->getProgressChanged() !== null) {
            if ($this->filter->getProgressChangedDirection() === $this->filter::TIME_BEFORE) {
                $where .= $db->quoteIdentifier('progress_changed') . ' <= ' .
                    $db->quote($this->filter->getProgressChanged(), ilDBConstants::T_INTEGER);
            } else {
                $where .= $db->quoteIdentifier('progress_changed') . ' >= ' .
                    $db->quote($this->filter->getProgressChanged(), ilDBConstants::T_INTEGER);
            }
            $where .= ' AND ';
        }

        /* Event related filter */
        if ($this->filter->getExcludedProgress()) {
            $where .= $db->quoteIdentifier('progress') . ' <> ' .
                $db->quote($this->filter->getExcludedProgress(), ilDBConstants::T_TEXT) . ' ';
            $where .= ' AND ';
        }
        if ($this->filter->getProgress() !== '*' && $this->filter->getProgress() !== '') {
            $where .= $db->quoteIdentifier('progress') . ' = ' .
                $db->quote($this->filter->getProgress(), ilDBConstants::T_TEXT) . ' ';
            $where .= ' AND ';
        }
        if ($this->filter->getAssignment() !== '*' && $this->filter->getAssignment() !== '') {
            $where .= $db->quoteIdentifier('assignment') . ' = ' .
                $db->quote($this->filter->getAssignment(), ilDBConstants::T_TEXT) . ' ';
            $where .= ' AND ';
        }

        /* Event type filter */
        if ($this->filter->getEventType() !== '*' && $this->filter->getEventType() !== '') {
            $where .= $db->quoteIdentifier('event_type') . ' = ' .
                $db->quote($this->filter->getEventType(), ilDBConstants::T_TEXT) . ' AND ';
        }

        /* simple filter */
        if ($this->filter->getEvent() !== '*' && $this->filter->getEvent() !== '') {
            $where .= $db->quoteIdentifier('event') . ' = ' .
                $db->quote($this->filter->getEvent(), ilDBConstants::T_TEXT) . ' ';
            $where .= ' AND ';
        }

        /* Paging filter */
        if ($this->filter->getPageStart() > 0) {
            if ($this->filter->isNegativePager()) {
                $where .= $db->quoteIdentifier('id') . ' < ' .
                    $db->quote($this->filter->getPageStart(), ilDBConstants::T_INTEGER) . ' ';
            } else {
                $where .= $db->quoteIdentifier('id') . ' > ' .
                    $db->quote($this->filter->getPageStart(), ilDBConstants::T_INTEGER) . ' ';
            }
            $where .= ' AND ';
        }

        if ($this->filter->getPageLength() !== -1) {
            $limit .= ' LIMIT ' . $this->filter->getPageLength() . ' ';
        }

        if ($this->filter->isNegativePager()) {
            $order .= ' ORDER BY ' . $db->quoteIdentifier('id') . ' DESC ';
        } else {
            $order .= ' ORDER BY ' . $db->quoteIdentifier('id') . ' ASC ';
        }

        if ($where !== '') {
            $where = ' WHERE ' . $where . ' TRUE ' . $order . $limit;

        } else {
            $where = $order . $limit;
        }

        return $where;
    }

    /**
     * @param list<array{"id": numeric-string, "event-type": string, "progress": null|string, "assignment": null|string, "user_data": string, "obj_data": string, "mem_data": string, "timestamp": numeric-string, "course_start": null|numeric-string, "course_end": null|numeric-string, "progress_changed": null|numeric-string}> $items
     */
    private function queryQueue(string $query, array &$items = []): void
    {
        $res = $this->database->query($query);

        while ($row = $this->database->fetchAssoc($res)) {
            $items[] = $row;
        }
    }

    /**
     * @param list<array{"id": numeric-string, "event-type": string, "progress": null|string, "assignment": null|string, "user_data": string, "obj_data": string, "mem_data": string, "timestamp": numeric-string, "course_start": null|numeric-string, "course_end": null|numeric-string, "progress_changed": null|numeric-string}> $items
     * @return list<array<int, QueueModel>>
     */
    private function buildModels(array $items): array
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

            $item_ud = json_decode($item['user_data'], true, 512, JSON_THROW_ON_ERROR);
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
            unset($item_ud, $um);

            $item_om = json_decode($item['obj_data'], true, 512, JSON_THROW_ON_ERROR);
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
            unset($item_om, $om);

            $item_mm = json_decode($item['mem_data'], true, 512, JSON_THROW_ON_ERROR);
            $mm = new MemberModel();
            $mm->setMemberRole($item_mm['role'])
                ->setCourseTitle($item_mm['course_title'])
                ->setCourseId($item_mm['course_id'])
                ->setCourseRefId($item_mm['course_ref_id']);
            $qm->setMemData($mm);
            unset($item_mm, $mm);

            $models[(int) $item['id']] = $qm;
            unset($qm);
        }

        return $models;
    }
}
