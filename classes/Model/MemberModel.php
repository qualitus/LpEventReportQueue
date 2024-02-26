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

class MemberModel
{
    private ?string $member_role = null;
    private ?string $course_title = null;
    private ?int $course_id = null;
    private ?int $course_ref_id = null;

    public function getMemberRole(): string
    {
        return $this->member_role ?? '';
    }

    /**
     * @param string $member_role
     */
    public function setMemberRole($member_role): self
    {
        $this->member_role = $member_role;
        return $this;
    }

    public function getCourseTitle(): string
    {
        return $this->course_title ?? '';
    }

    /**
     * @param string $course_title
     */
    public function setCourseTitle($course_title): self
    {
        $this->course_title = $course_title;
        return $this;
    }

    public function getCourseId(): int
    {
        return $this->course_id ?? -1;
    }

    /**
     * @param int $course_id
     */
    public function setCourseId($course_id): self
    {
        $this->course_id = $course_id;
        return $this;
    }

    public function getCourseRefId(): int
    {
        return $this->course_ref_id ?? -1;
    }

    /**
     * @param int $course_ref_id
     */
    public function setCourseRefId($course_ref_id): self
    {
        $this->course_ref_id = $course_ref_id;
        return $this;
    }

    public function __toString(): string
    {
        return json_encode([
            'role' => $this->getMemberRole(),
            'course_title' => $this->getCourseTitle(),
            'course_id' => $this->getCourseId(),
            'course_ref_id' => $this->getCourseRefId(),
        ], JSON_THROW_ON_ERROR);
    }
}
