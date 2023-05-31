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

use IteratorAggregate;

class QueueCollection implements IteratorAggregate
{
    /** @var array */
    private array $items = [];
    private ?CollectionIterator $iterator = null;

    /**
     * @param array $items
     * @return $this|bool
     */
    public function create(array $items)
    {
        if (!is_array($items)) {
            return false;
        }

        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllItems(): array
    {
        return $this->items;
    }

    /**
     * @return list<string|int>
     */
    public function getItemKeys(): array
    {
        $current = $this->getIterator()->current();
        if (!is_array($current)) {
            $current = [];
        }

        return array_keys($current);
    }

    public function getIterator(bool $getnew = false): CollectionIterator
    {
        if (!isset($this->iterator) || $getnew === true) {
            $this->iterator = new CollectionIterator($this->items);
        }

        return $this->iterator;
    }
}
