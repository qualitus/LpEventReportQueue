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

declare(strict_types=1);

namespace QU\LERQ\API\Table\Formatter;

use QU\LERQ\UI\Table\Formatter;

class JsonDocumentFormatter implements Formatter
{
    public function format($value): string
    {
        assert(is_string($value) || is_null($value), '$value is not a string and not null');

        if (is_null($value)) {
            return '';
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return '<pre>' . var_export($decoded, true) . '</pre>';
    }
}
