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

class RoutinesModel
{
    private bool $collectUserData = false;
    private bool $collectUDFData = false;
    private bool $collectMemberData = false;
    private bool $collectLpPeriod = false;
    private bool $collectObjectData = false;

    public function getCollectUserData(): bool
    {
        return $this->collectUserData ?? false;
    }

    public function setCollectUserData(bool $collectUserData): self
    {
        $this->collectUserData = $collectUserData;
        return $this;
    }

    public function getCollectUDFData(): bool
    {
        return $this->collectUDFData ?? false;
    }

    public function setCollectUDFData(bool $collectUDFData): self
    {
        $this->collectUDFData = $collectUDFData;
        return $this;
    }

    public function getCollectMemberData(): bool
    {
        return $this->collectMemberData ?? false;
    }

    public function setCollectMemberData(bool $collectMemberData): self
    {
        $this->collectMemberData = $collectMemberData;
        return $this;
    }

    public function getCollectLpPeriod(): bool
    {
        return $this->collectLpPeriod ?? false;
    }

    public function setCollectLpPeriod(bool $collectLpPeriod): self
    {
        $this->collectLpPeriod = $collectLpPeriod;
        return $this;
    }

    public function getCollectObjectData(): bool
    {
        return $this->collectObjectData ?? false;
    }

    public function setCollectObjectData(bool $collectObjectData): self
    {
        $this->collectObjectData = $collectObjectData;
        return $this;
    }

    public function __toString(): string
    {
        return json_encode([
            'collectUserData' => $this->getCollectUserData(),
            'collectUDFData' => $this->getCollectUDFData(),
            'collectMemberData' => $this->getCollectMemberData(),
            'collectLpPeriod' => $this->getCollectLpPeriod(),
            'getCollectObjectData' => $this->getCollectObjectData(),
        ], JSON_THROW_ON_ERROR);
    }
}
