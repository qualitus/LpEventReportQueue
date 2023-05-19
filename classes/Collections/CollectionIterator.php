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

namespace QU\LERQ\Collections;

use Iterator;

class CollectionIterator implements Iterator
{
    /** @var list<array{"id": numeric-string, "event-type": string, "progress": null|string, "assignment": null|string, "user_data": string, "obj_data": string, "mem_data": string, "timestamp": numeric-string, "course_start": null|numeric-string, "course_end": null|numeric-string, "progress_changed": null|numeric-string}> */
    private array $items;

    /**
     * @param list<array{"id": numeric-string, "event-type": string, "progress": null|string, "assignment": null|string, "user_data": string, "obj_data": string, "mem_data": string, "timestamp": numeric-string, "course_start": null|numeric-string, "course_end": null|numeric-string, "progress_changed": null|numeric-string}> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return false|array{"id": numeric-string, "event-type": string, "progress": null|string, "assignment": null|string, "user_data": string, "obj_data": string, "mem_data": string, "timestamp": numeric-string, "course_start": null|numeric-string, "course_end": null|numeric-string, "progress_changed": null|numeric-string}
     */
    public function current()
    {
        return current($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }

    public function rewind(): void
    {
        reset($this->items);
    }
}
