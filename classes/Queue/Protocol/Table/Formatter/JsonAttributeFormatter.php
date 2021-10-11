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

use QU\LERQ\UI\Table\Formatter;

class JsonAttributeFormatter implements Formatter
{
    private $attribute;

    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    public function format($value) : string
    {
        assert(is_string($value) || is_null($value), '$value is not a string and not null');

        if (is_null($value)) {
            return '';
        }

        $decoded = json_decode($value, true);
        if (!isset($decoded[$this->attribute])) {
            return '';
        }

        return (string) $decoded[$this->attribute];
    }
}