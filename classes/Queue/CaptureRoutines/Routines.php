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

namespace QU\LERQ\Queue\CaptureRoutines;

use ilDBConstants;
use ilLink;
use ilObjCourse;
use ilObject;
use ilObjectFactory;
use ilObjUser;
use ilUserDefinedData;
use ilUserDefinedFields;
use ilUtil;
use QU\LERQ\API\DataCaptureRoutinesInterface;
use QU\LERQ\Model\EventModel;

class Routines implements DataCaptureRoutinesInterface
{
    /**
     * @return list<string>
     */
    public function getAvailableOverrrides(): array
    {
        return [
            'collectUserData',
            'collectUDFData',
            'collectMemberData',
            'collectLpPeriod',
            'collectObjectData',
        ];
    }

    public function getOverrides(): array
    {
        return [];
    }

    public function collectUserData(EventModel $event): array
    {
        $data = [];

        if ($event->getUsrId() !== -1) {
            $user = new ilObjUser($event->getUsrId());
            $data['user_id'] = $user->getId();
            $data['username'] = $user->getLogin();
            $data['firstname'] = $user->getFirstname();
            $data['lastname'] = $user->getLastname();
            $data['title'] = $user->getTitle();
            $data['gender'] = $user->getGender();
            $data['email'] = $user->getEmail();
            $data['institution'] = $user->getInstitution();
            $data['street'] = $user->getStreet();
            $data['city'] = $user->getCity();
            $data['country'] = $user->getCountry();
            $data['phone_office'] = $user->getPhoneOffice();
            $data['hobby'] = $user->getHobby();
            $data['department'] = $user->getDepartment();
            $data['phone_home'] = $user->getPhoneHome();
            $data['phone_mobile'] = $user->getPhoneMobile();
            $data['phone_fax'] = $user->getFax();
            $data['referral_comment'] = $user->getComment();
            $data['matriculation'] = $user->getMatriculation();
            $data['active'] = $user->getActive();
            $data['approval_date'] = $user->getApproveDate();
            $data['agree_date'] = $user->getAgreeDate();
            $data['auth_mode'] = $user->getAuthMode();
            $data['ext_account'] = $user->getExternalAccount();
            $data['birthday'] = $user->getBirthday();
            $data['import_id'] = $user->getImportId();
        }

        return $data;
    }

    public function collectUDFData(EventModel $event): array
    {
        $data = [];

        if ($event->getUsrId() !== -1) {
            $udfObj = ilUserDefinedFields::_getInstance();
            $udef = $udfObj->getVisibleDefinitions();

            $uddObj = new ilUserDefinedData($event->getUsrId());
            $udata = $uddObj->getAll();

            foreach ($udef as $field_id => $definition) {
                $data[$field_id] = $udata[$field_id] ?? null;
            }
        }

        return $data;
    }

    public function collectMemberData(EventModel $event): array
    {
        $data = [];

        if ($event->getUsrId() !== -1 && ($event->getObjId() !== -1 || $event->getRefId() !== -1)) {
            /** @var ilObject $ilObj */
            if ($event->getRefId() !== -1) {
                $ilObj = ilObjectFactory::getInstanceByRefId($event->getRefId(), false);
            } else {
                $ilObj = ilObjectFactory::getInstanceByObjId($event->getObjId(), false);
            }

            if ($ilObj === null) {
                return $data;
            }

            // check if object is type course
            if ($ilObj->getType() === 'crs') {
                $crs_title = $ilObj->getTitle();
                $crs_id = $ilObj->getId();
                $crs_ref_id = $ilObj->getRefId();
            } else {
                // check if any parent object is of type course
                $parent = $this->findParentCourse($ilObj->getRefId());
                if ($parent === 0) {
                    $crs_title = null;
                    $crs_id = null;
                    $crs_ref_id = null;
                } else {
                    $parentObj = new ilObjCourse($parent, true);
                    $crs_title = $parentObj->getTitle();
                    $crs_id = $parentObj->getId();
                    $crs_ref_id = $parentObj->getRefId();
                }
            }

            // bugfix mantis 6876
            // if we got no role_id, we try to figure out the assignment role of user <-> course relation
            $rol_id = $event->getRoleId();
            if ($rol_id === -1) {
                // if crs_ref_id is not known, we try to get it
                if ($crs_ref_id === null) {
                    $set = $this->findFirstParentCourseByObjId($ilObj->getId());
                    if ($set['ref_id'] > 0) {
                        // to get the correct role, we need the object ref_id
                        $ilObj->setRefId($set['ref_id']);

                        if ($set['course_ref_id'] > 0) {
                            $course = new ilObjCourse($set['course_ref_id'], true);
                            $crs_title = $course->getTitle();
                            $crs_id = $course->getId();
                            $crs_ref_id = $course->getRefId();
                        }
                    }
                }

                $rol_id = $this->getRoleAssignmentByUserIdAndCourseId(
                    $event->getUsrId(),
                    $event->getRefId() !== -1 ? $ilObj->getRefId() : $ilObj->getId(),
                    $event->getRefId() !== -1
                );
            }

            $data['role'] = $rol_id !== -1 ? $rol_id : null;
            $data['course_title'] = $crs_title;
            $data['course_id'] = $crs_id;
            $data['course_ref_id'] = $crs_ref_id;
        }

        return $data;
    }

    public function collectLpPeriod(EventModel $event): array
    {
        $data = [];

        if ($event->getObjId() !== -1 || $event->getRefId() !== -1) {
            /** @var ilObject $ilObj */
            if ($event->getRefId() !== -1) {
                $ilObj = ilObjectFactory::getInstanceByRefId($event->getRefId(), false);
            } else {
                $ilObj = ilObjectFactory::getInstanceByObjId($event->getObjId(), false);
            }

            if ($ilObj === null) {
                return $data;
            }

            $course = null;
            if ($ilObj->getType() === 'crs') {
                $course = $ilObj;
            } elseif ($ilObj->getRefId() > 0) {
                $parent = $this->findParentCourse($ilObj->getRefId());
                if ($parent !== 0) {
                    $course = ilObjectFactory::getInstanceByRefId($parent, false);
                }
            }

            /** @var null|ilObjCourse $ilObj */
            if ($course !== null) {
                $data['course_start'] = $course->getCourseStart();
                $data['course_end'] = $course->getCourseEnd();
            }
        }

        return $data;
    }

    public function collectObjectData(EventModel $event): array
    {
        global $DIC;

        $data = [];

        if ($event->getObjId() !== -1 || $event->getRefId() !== -1) {
            /** @var ilObject $ilObj */
            if ($event->getRefId() !== -1) {
                $ilObj = ilObjectFactory::getInstanceByRefId($event->getRefId(), false);
            } else {
                $ilObj = ilObjectFactory::getInstanceByObjId($event->getObjId(), false);
            }

            if ($ilObj === null) {
                return $data;
            }

            $course_id = -1;
            if ($ilObj->getType() === 'crs') {
                /** @var ilObjCourse $ilObj */
                $course_id = $ilObj->getRefId();
            } else {
                $parent = $this->findParentCourse($ilObj->getRefId());
                if ($parent > 0) {
                    $course_id = $parent;
                }
            }

            // bugfix mantis #6880
            // if no course can be found because the event does not know the ref_id,
            // search for any object matching the obj_id
            $ambiguous = '';
            if ($course_id === -1) {
                $set = $this->findFirstParentCourseByObjId($event->getObjId(), $ilObj->getType() === 'crs');
                if ($set['ref_id'] > 0) {
                    $ilObj->setRefId($set['ref_id']);

                    if ($set['course_ref_id'] > 0) {
                        $course_id = $set['course_ref_id'];
                    }
                }
            }

            if ($event->getRefId() === -1) {
                $ambiguous = '&ambiguous=true';
            }

            $crs_title = null;
            $crs_id = null;
            $crs_ref_id = null;
            if ($course_id > 0) {
                $course = new ilObjCourse($course_id, true);
                $crs_title = $course->getTitle();
                $crs_id = $course->getId();
                $crs_ref_id = $course->getRefId();
            }

            $link = '';
            if ($ilObj->getRefId() !== null && $ilObj->getType() !== null) {
                if (!defined('ILIAS_HTTP_PATH')) {
                    define('ILIAS_HTTP_PATH', ilUtil::_getHttpPath());
                }
                $link = ilLink::_getStaticLink($ilObj->getRefId(), $ilObj->getType());
            }

            $data['id'] = $ilObj->getId();
            $data['title'] = $ilObj->getTitle();
            $data['ref_id'] = $ilObj->getRefId();
            $data['link'] = $link . $ambiguous;
            $data['type'] = $ilObj->getType();
            $data['course_title'] = $crs_title;
            $data['course_id'] = $crs_id;
            $data['course_ref_id'] = $crs_ref_id;
        }

        return $data;
    }

    /**
     * Find parent course object
     *
     * @return int
     *         Returns 0 (zero) if no parent course could be found
     *         otherwise the ref_id of the course object
     */
    protected function findParentCourse(?int $ref_id): int
    {
        global $DIC;

        if ($ref_id !== null && $ref_id > 0) {
            $tree = $DIC->repositoryTree();
            $parent = 0;
            // check if parent object is type course
            $parent_type = $tree->checkForParentType($ref_id, 'crs');

            if ($parent_type === 0) {
                // walk tree and check if parent object of any node is type course
                $paths = $tree->getPathFull($ref_id, ROOT_FOLDER_ID);
                if ($paths !== []) {
                    foreach (array_reverse($paths) as $path) {
                        $parent_type = $tree->checkForParentType((int) $path['child'], 'crs');
                        if ($parent_type > 0) {
                            $parent = (int) $path['child'];
                            break;
                        }
                    }
                }
            } else {
                $parent = $parent_type;
            }

            if ($parent_type === 0) {
                return 0;
            }

            return $parent;
        }

        return 0;
    }

    /**
     * Find first ref_id of object and parent course ref_id
     *
     * This function is returns "any" matching ref id of the object. It should only be called
     * if no course can be found because because the object has no ref id known to the event.
     * @bugfix mantis #6880
     * @return array{"ref_id": int, "course_ref_id": int}
     *        [
     *            'ref_id' => (int) object ref id | or zero if nothing is found
     *            'course_ref_id' => (int) course ref id | or zero if nothing is found
     *        ]
     */
    protected function findFirstParentCourseByObjId(int $obj_id, bool $is_course = false): array
    {
        global $DIC;

        $set = [
            'ref_id' => 0
        ];

        $sql = 'SELECT ref_id FROM object_reference WHERE obj_id = ' .
            $DIC->database()->quote($obj_id, ilDBConstants::T_INTEGER) .
            ' AND deleted IS NULL LIMIT 1;';

        $result = $DIC->database()->query($sql);
        $ref = $DIC->database()->fetchAll($result);

        if ($ref !== [] && array_key_exists('ref_id', $ref[0])) {
            $set['ref_id'] = (int) $ref[0]['ref_id'];
        }

        if (!$is_course) {
            $set['course_ref_id'] = $this->findParentCourse($set['ref_id'] === 0 ? null : $set['ref_id']);
        } else {
            $set['course_ref_id'] = $set['ref_id'];
        }

        return $set;
    }

    /**
     * Get role id of user and course relation
     * @return int -1 if no assignment can be found. Otherwise role_id
     */
    public function getRoleAssignmentByUserIdAndCourseId(
        int $user_id,
        int $course_id,
        bool $call_course_by_ref = false
    ): int {
        global $DIC;

        $select_assignments = 'SELECT rua.rol_id FROM object_reference oref ' .
            'LEFT JOIN rbac_fa rfa ON rfa.parent = oref.ref_id ' .
            'LEFT JOIN rbac_ua rua ON rua.rol_id = rfa.rol_id ' .
            'WHERE rfa.assign = ' . $DIC->database()->quote('y', ilDBConstants::T_TEXT) . ' ' .
            'AND rua.rol_id IS NOT NULL ' .
            'AND rua.usr_id = ' . $DIC->database()->quote($user_id, ilDBConstants::T_INTEGER) . ' ';

        if ($call_course_by_ref) {
            $select_assignments .= 'AND oref.ref_id = ' . $DIC->database()->quote($course_id, ilDBConstants::T_INTEGER);
        } else {
            $select_assignments .= 'AND oref.obj_id = ' . $DIC->database()->quote($course_id, ilDBConstants::T_INTEGER);
        }

        $result = $DIC->database()->query($select_assignments);
        $assignments = $DIC->database()->fetchAll($result);
        $DIC->database()->free($result);
        unset($result);

        if ($assignments !== [] && array_key_exists('rol_id', $assignments[0])) {
            return (int) $assignments[0]['rol_id'];
        }

        return -1;
    }
}
