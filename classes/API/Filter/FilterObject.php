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

namespace QU\LERQ\API\Filter;

class FilterObject // @todo check filter rules
{
    public const TIME_BEFORE = 0;
    public const TIME_AFTER = 1;

    private ?int $course_start = null;
    private int $course_start_direction = self::TIME_AFTER;
    private ?int $course_end = null;
    private int $course_end_direction = self::TIME_BEFORE;
    private string $progress = '*';
    private ?int $page_start = null;
    private ?int $page_length = null;
    private ?int $event_happened_start = null;
    private ?int $event_happened_end = null;
    private ?int $event_happened = null;
    private int $event_happened_direction = self::TIME_AFTER;
    private ?int $progress_changed = null;
    private int $progress_changed_direction = self::TIME_AFTER;
    private string $assignment = '*';
    private string $event = '*';
    private string $event_type = '*';
    private bool $negative_pager = false;
    private string $excluded_progress = '';

    public function getCourseStartDirection(): int
    {
        return $this->course_start_direction;
    }

    public function getCourseStart(): ?int
    {
        return $this->course_start;
    }

    public function setCourseStart(int $course_start, int $before_after = self::TIME_AFTER): self
    {
        $this->course_start = $course_start;
        $this->course_start_direction = $before_after;
        return $this;
    }

    public function getCourseEndDirection(): int
    {
        return $this->course_end_direction;
    }

    public function getCourseEnd(): ?int
    {
        return $this->course_end;
    }

    public function setCourseEnd(int $course_end, int $before_after = self::TIME_BEFORE): self
    {
        $this->course_end = $course_end;
        $this->course_end_direction = $before_after;
        return $this;
    }

    public function getExcludedProgress(): string
    {
        return $this->excluded_progress;
    }

    public function setExcludedProgress(string $excluded_progress): self
    {
        $this->excluded_progress = $excluded_progress;
        return $this;
    }

    public function getProgress(): string
    {
        return $this->progress;
    }

    public function setProgress(string $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    public function getPageStart(): int
    {
        return $this->page_start ?? 0;
    }

    public function setPageStart(int $page_start): self
    {
        $this->page_start = $page_start;
        return $this;
    }

    public function getPageLength(): int
    {
        return $this->page_length ?? 500;
    }

    public function setPageLength(int $page_length): self
    {
        $this->page_length = $page_length;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function setEventType(string $event_type): self
    {
        $this->event_type = $event_type;
        return $this;
    }

    public function getEventHappenedDirection(): int
    {
        return $this->event_happened_direction;
    }

    public function getEventHappened(): ?int
    {
        return $this->event_happened;
    }

    public function setEventHappened(int $event_happened, int $before_after = self::TIME_AFTER): self
    {
        $this->event_happened = $event_happened;
        $this->event_happened_direction = $before_after;
        return $this;
    }

    public function getEventHappenedStart(): ?int
    {
        return $this->event_happened_start;
    }

    public function setEventHappenedStart(int $event_happened_start): self
    {
        $this->event_happened_start = $event_happened_start;
        return $this;
    }

    public function getEventHappenedEnd(): ?int
    {
        return $this->event_happened_end;
    }

    public function setEventHappenedEnd(int $event_happened_end): self
    {
        $this->event_happened_end = $event_happened_end;
        return $this;
    }

    public function getAssignment(): string
    {
        return $this->assignment;
    }

    public function setAssignment(string $assignment): self
    {
        $this->assignment = $assignment;
        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function isNegativePager(): bool
    {
        return $this->negative_pager;
    }

    public function setNegativePager(bool $negative_pager): self
    {
        $this->negative_pager = $negative_pager;
        return $this;
    }

    public function getProgressChangedDirection(): int
    {
        return $this->progress_changed_direction;
    }

    public function getProgressChanged(): ?int
    {
        return $this->progress_changed;
    }

    public function setProgressChanged(int $progress_changed, int $before_after = self::TIME_AFTER): self
    {
        $this->progress_changed = $progress_changed;
        $this->progress_changed_direction = $before_after;
        return $this;
    }
}
