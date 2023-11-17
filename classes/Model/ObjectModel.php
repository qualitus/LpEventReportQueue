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

class ObjectModel
{
    private ?string $title = null;
    private ?int $id = null;
    private ?int $ref_id = null;
    private ?string $link = null;
    private ?string $type = null;
    private ?string $type_hr = null;
    private ?string $course_title = null;
    private ?int $course_id = null;
    private ?int $course_ref_id = null;

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * @param string $title
     */
    public function setTitle($title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getId(): int
    {
        return $this->id ?? -1;
    }

    /**
     * @param int $id
     */
    public function setId($id): self
    {
        $this->id = is_numeric($id) ? (int) $id : $id;
        return $this;
    }

    public function getRefId(): int
    {
        return $this->ref_id ?? -1;
    }

    /**
     * @param int $ref_id
     */
    public function setRefId($ref_id): self
    {
        $this->ref_id = is_numeric($ref_id) ? (int) $ref_id : $ref_id;
        return $this;
    }

    public function getLink(): string
    {
        return $this->link ?? '';
    }

    /**
     * @param string $link
     */
    public function setLink($link): self
    {
        $this->link = $link;
        return $this;
    }

    public function getType(): string
    {
        return $this->type ?? '';
    }

    /**
     * @param string $type
     */
    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeHr(): string
    {
        return $this->type_hr ?? $this->translateType();
    }

    /**
     * @param string $type_hr
     */
    public function setTypeHr($type_hr): self
    {
        $this->type_hr = $type_hr;
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
        $this->course_id = is_numeric($course_id) ? (int) $course_id : $course_id;
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
        $this->course_ref_id = is_numeric($course_ref_id) ? (int) $course_ref_id : $course_ref_id;
        return $this;
    }

    public function translateType(): string
    {
        if ($this->getType() !== '') {
            switch ($this->getType()) {
                case 'adm':
                    return 'SystemFolder';

                case 'assf':
                    return 'AssessentFolder';

                case 'bibl':
                    return 'Bibliographic';

                case 'blog':
                    return 'Blog';

                case 'book':
                    return 'BookingPool';

                case 'cat':
                    return 'Category';

                case 'catr':
                    return 'CategoryReference';

                case 'crs':
                    return 'Course';

                case 'crsr':
                    return 'CourseReference';

                case 'dcl':
                    return 'DataCollection';

                case 'exc':
                    return 'Excercise';

                case 'fold':
                    return 'Folder';

                case 'frm':
                    return 'Forum';

                case 'glo':
                    return 'Glossary';

                case 'grp':
                    return 'Group';

                case 'grpr':
                    return 'GroupReference';

                case 'iass':
                    return 'IndividualAssessment';

                case 'lm':
                    return 'LearningModule';

                case 'prg':
                    return 'StudyProgramme';

                case 'role':
                    return 'Role';

                case 'rolf':
                    return 'RoleFolder';

                case 'sahs':
                    return 'SAHSLearningModule';

                case 'sess':
                    return 'Session';

                case 'trac':
                    return 'UserTracking';

                case 'tst':
                    return 'Test';

                case 'usr':
                    return 'User';
            }
        }

        return '';
    }

    public function __toString(): string
    {
        return json_encode([
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'ref_id' => $this->getRefId(),
            'link' => $this->getLink(),
            'type' => $this->getType(),
            'type_hr' => $this->getTypeHr(),
            'course_title' => $this->getCourseTitle(),
            'course_id' => $this->getCourseId(),
            'course_ref_id' => $this->getCourseRefId(),
        ], JSON_THROW_ON_ERROR);
    }
}
