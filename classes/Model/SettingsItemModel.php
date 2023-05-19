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

class SettingsItemModel
{
    private string $keyword;
    /** @var mixed|null */
    private $value;

    /**
     * @param mixed|null $value
     */
    public function __construct(string $keyword, $value = null)
    {
        $this->keyword = $keyword;
        if (isset($value)) {
            $this->setValue($value);
        }
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed|null $value
     */
    public function setValue($value): SettingsItemModel
    {
        $this->value = $value;
        return $this;
    }
}
