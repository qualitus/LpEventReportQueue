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

class EventModel
{
    /** @var int */
    private $obj_id;
    /** @var int */
    private $ref_id;
    /** @var int */
    private $parent_ref_id;
    /** @var string */
    private $obj_type;
    /** @var int */
    private $usr_id;
    /** @var int */
    private $role_id;
    /** @var array */
    private $appointments;
    /** @var string */
    private $lp_status;
    /** @var int */
    private $lp_percentage;
    /** @var string */
    private $event_name;

    public function getObjId(): int
    {
        return $this->obj_id ?? -1;
    }

    public function setObjId(int $obj_id): self
    {
        $this->obj_id = $obj_id;
        return $this;
    }

    public function getRefId(): int
    {
        return $this->ref_id ?? -1;
    }

    public function setRefId(int $ref_id): self
    {
        $this->ref_id = $ref_id;
        return $this;
    }

    public function getParentRefId(): int
    {
        return $this->parent_ref_id ?? -1;
    }

    public function setParentRefId(int $parent_ref_id): self
    {
        $this->parent_ref_id = $parent_ref_id;
        return $this;
    }

    public function getObjType(): string
    {
        return $this->obj_type ?? '';
    }

    public function setObjType(string $obj_type): self
    {
        $this->obj_type = $obj_type;
        return $this;
    }

    public function getUsrId(): int
    {
        return $this->usr_id ?? -1;
    }

    public function setUsrId(int $usr_id): self
    {
        $this->usr_id = $usr_id;
        return $this;
    }

    public function getRoleId(): int
    {
        return $this->role_id ?? -1;
    }

    public function setRoleId(int $role_id): self
    {
        $this->role_id = $role_id;
        return $this;
    }

    /**
     * @return array
     */
    public function getAppointments(): array
    {
        return $this->appointments ?? [];
    }

    /**
     * @param array $appointments
     */
    public function setAppointments(array $appointments): self
    {
        $this->appointments = $appointments;
        return $this;
    }

    public function getLpStatus(): string
    {
        return $this->lp_status ?? '';
    }

    public function setLpStatus(string $lp_status): self
    {
        $this->lp_status = $lp_status;
        return $this;
    }

    public function getLpPercentage(): int
    {
        return $this->lp_percentage ?? -1;
    }

    public function setLpPercentage(int $lp_percentage): self
    {
        $this->lp_percentage = $lp_percentage;
        return $this;
    }

    public function getEventName(): string
    {
        return $this->event_name ?? '';
    }

    public function setEventName(string $event_name): self
    {
        $this->event_name = $event_name;
        return $this;
    }
}
