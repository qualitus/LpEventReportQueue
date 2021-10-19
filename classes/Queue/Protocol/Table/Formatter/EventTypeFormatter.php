<?php declare(strict_types=1);

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
 ********************************************************************
 */

namespace QU\LERQ\Queue\Protocol\Table\Formatter;

use InvalidArgumentException;
use QU\LERQ\UI\Table\Formatter;

class EventTypeFormatter implements Formatter
{
    private $event_field;
    private $event_type_field;

    public function __construct(string $event_field, string $event_type_field)
    {
        $this->event_field = $event_field;
        $this->event_type_field = $event_type_field;
    }

    public function format($value) : string
    {
        assert(is_array($value), '$value is not an array');

        if (!isset($value[$this->event_field])) {
            throw new InvalidArgumentException(sprintf('Missing key %s in $value', $this->event_field));
        }

        if (!isset($value[$this->event_type_field])) {
            throw new InvalidArgumentException(sprintf('Missing key %s in $value', $this->event_type_field));
        }

        return $value[$this->event_type_field] . ' -> ' . $value[$this->event_field];
    }
}