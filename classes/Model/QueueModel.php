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

use DateTime;
use DateTimeZone;

class QueueModel
{
    /** @var int */
    private $id;
    /** @var string|int|null */
    private $timestamp;
    /** @var string („progress_changed“, „progress_reset“, etc.) */
    private $event;
    /** @var string ("lp_event", "member_event", "object_event") */
    private $event_type;
    /** @var string */
    private $progress;
    /** @var string|int|null */
    private $progress_changed;
    /** @var string */
    private $assignment;
    /** @var string|int|null */
    private $course_start;
    /** @var string|int|null */
    private $course_end;
    /** @var UserModel */
    private $user_data;
    /** @var ObjectModel */
    private $obj_data;
    /** @var MemberModel */
    private $mem_data;

    public function getId(): int
    {
        return $this->id ?? -1;
    }

    /**
     * @param int $id
     */
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|int|null
     */
    public function getTimestamp(bool $iso = false, string $timezone = 'UTC')
    {
        if ($iso) {
            if ($timestamp = ($this->timestamp ?? 'now')) {
                $dt = new DateTime();
                if (is_numeric($timestamp)) {
                    $dt->setTimestamp($timestamp * 1);
                } else {
                    $dt->setTimestamp(strtotime($timestamp));
                }
                $dt->setTimezone(new DateTimeZone($timezone));

                return $dt->format('c');
            }

            return '';
        }

        return $this->timestamp ?? null;
    }

    /**
     * @param int|string $timestamp
     */
    public function setTimestamp($timestamp): self
    {
        if (is_numeric($timestamp)) {
            $this->timestamp = (int) ($timestamp * 1);
        } elseif (is_string($timestamp)) {
            $this->timestamp = strtotime($timestamp);
        }

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event ?? '';
    }

    /**
     * @param string $event
     */
    public function setEvent($event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->event_type ?? '';
    }

    /**
     * @param string $event_type
     */
    public function setEventType($event_type): self
    {
        $this->event_type = $event_type;
        return $this;
    }

    public function getProgress(): string
    {
        return $this->progress ?? '';
    }

    /**
     * @param string $progress
     */
    public function setProgress($progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * @return string|int|null
     */
    public function getProgressChanged(bool $iso = false)
    {
        if ($iso) {
            return isset($this->progress_changed) ? date('c', $this->progress_changed) : '';
        }

        return $this->progress_changed ?? null;
    }

    /**
     * @param string $progress_changed
     */
    public function setProgressChanged($progress_changed): self
    {
        $this->progress_changed = $progress_changed;
        return $this;
    }

    public function getAssignment(): string
    {
        return $this->assignment ?? '';
    }

    /**
     * @param string $assignment
     */
    public function setAssignment($assignment): self
    {
        $this->assignment = $assignment;
        return $this;
    }

    /**
     * @return string|int|null
     */
    public function getCourseStart(bool $iso = false)
    {
        if ($iso) {
            return isset($this->course_start) ? date('c', $this->course_start) : '';
        }

        return $this->course_start ?? null;
    }

    /**
     * @param string $course_start
     */
    public function setCourseStart($course_start): self
    {
        $this->course_start = $course_start;
        return $this;
    }

    /**
     * @return string|int|null
     */
    public function getCourseEnd(bool $iso = false)
    {
        if ($iso) {
            return isset($this->course_end) ? date('c', $this->course_end) : '';
        }

        return $this->course_end ?? null;
    }

    /**
     * @param string $course_end
     */
    public function setCourseEnd($course_end): self
    {
        $this->course_end = $course_end;
        return $this;
    }

    public function getUserData(): UserModel
    {
        return $this->user_data;
    }

    public function setUserData(UserModel $user_data): self
    {
        $this->user_data = $user_data;
        return $this;
    }

    public function getObjData(): ObjectModel
    {
        return $this->obj_data;
    }

    public function setObjData(ObjectModel $obj_data): self
    {
        $this->obj_data = $obj_data;
        return $this;
    }

    public function getMemData(): MemberModel
    {
        return $this->mem_data;
    }

    public function setMemData(MemberModel $mem_data): self
    {
        $this->mem_data = $mem_data;
        return $this;
    }

    public function __toString(): string
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
            'user_data' => json_decode($this->getUserData(), false, 512, JSON_THROW_ON_ERROR),
            'obj_data' => json_decode($this->getObjData(), false, 512, JSON_THROW_ON_ERROR),
            'mem_data' => json_decode($this->getMemData(), false, 512, JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);
    }
}
