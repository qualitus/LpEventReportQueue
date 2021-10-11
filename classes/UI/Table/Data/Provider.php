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

namespace QU\LERQ\UI\Table\Data;

interface Provider
{
    /**
     * @param array<string, mixed> $params Table parameters like limit or order
     * @param array<string, mixed> $filter Filter settings provided by a ilTable2GUI instance
     * @return array<array<string, mixed>> An associative array with keys 'items' (array of items) and 'cnt' (number of total items)
     */
    public function getList(array $params, array $filter) : array;
}
