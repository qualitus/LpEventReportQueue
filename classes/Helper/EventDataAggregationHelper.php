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

namespace QU\LERQ\Helper;

use ilDBConstants;
use ilLogger;
use ilLPStatus;
use ilObject;
use ilObjectFactory;
use ilObjRole;

class EventDataAggregationHelper
{
    private static ?self $instance = null;

    private ilLogger $logger;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
    }

    public static function singleton(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param numeric-string|int $status
     * Get learning progress status representation by lp.status
     */
    public function getLpStatusRepresentation($status = 0): string
    {
        $lpStatus = '';
        switch ($status) {
            case ilLPStatus::LP_STATUS_IN_PROGRESS_NUM:
                $lpStatus = 'in_progress';
                break;
            case ilLPStatus::LP_STATUS_COMPLETED_NUM:
                $lpStatus = 'completed';
                break;
            case ilLPStatus::LP_STATUS_FAILED_NUM:
                $lpStatus = 'failed';
                break;
            case ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM:
            default:
                $lpStatus = 'no_attempted';
                break;
        }

        return $lpStatus;
    }

    /**
     * Get numeric LP status
     */
    public function getLpStatusByUsrAndObjId(int $user_id, int $obj_id): int
    {

        if (!isset($user_id, $obj_id)) {
            return 0;
        }
        global $DIC;

        $this->logger->debug(sprintf(
            'called "%s" with obj_id "%s" and user_id "%s"',
            'getLpStatusByUsrAndObjId',
            $obj_id,
            $user_id
        ));

        $query_status = 'SELECT status FROM ut_lp_marks ulm ' .
            'WHERE ulm.obj_id = ' . $DIC->database()->quote($obj_id, ilDBConstants::T_INTEGER) . ' ' .
            'AND ulm.usr_id = ' . $DIC->database()->quote($user_id, ilDBConstants::T_INTEGER);

        $result = $DIC->database()->query($query_status);
        $lp_status = $DIC->database()->fetchAll($result);

        if ($lp_status !== [] && array_key_exists('status', $lp_status[0])) {
            $this->logger->debug(sprintf('lp_status %s found', $lp_status[0]['status']));
            return (int) $lp_status[0]['status'];
        }

        $this->logger->debug('no lp_status found');
        return 0;
    }

    /**
     * Get LP status data
     * @return int|array{"obj_id": numeric-string, "usr_id": numeric-string, "status": numeric-string, "completed": numeric-string, "mark": null|string, "u_comment": null|string, "status_changed": null|string, "status_dirty": numeric-string, "percentage": numeric-string}
     */
    public function getLpStatusInfoByUsrAndObjId(int $user_id, int $obj_id)
    {
        global $DIC;

        if (!isset($user_id, $obj_id)) {
            return 0;
        }

        $this->logger->debug(sprintf(
            'called "%s" with obj_id "%s" and user_id "%s"',
            'getLpStatusByUsrAndObjId',
            $obj_id,
            $user_id
        ));

        $query_status = 'SELECT * FROM ut_lp_marks ulm ' .
            'WHERE ulm.obj_id = ' . $DIC->database()->quote($obj_id, ilDBConstants::T_INTEGER) . ' ' .
            'AND ulm.usr_id = ' . $DIC->database()->quote($user_id, ilDBConstants::T_INTEGER);

        $result = $DIC->database()->query($query_status);
        $lp_status = $DIC->database()->fetchAll($result);

        if ($lp_status !== [] && array_key_exists('status', $lp_status[0])) {
            $this->logger->debug('lp_status data found');
            return $lp_status[0];
        }

        $this->logger->debug('no lp_status data found');
        return 0;

    }

    /**
     * Get LP status last change time
     */
    public function getLpStatusChangedByUsrAndObjId(int $user_id, int $obj_id): int
    {
        global $DIC;

        $this->logger->debug(sprintf(
            'called "%s" with obj_id "%s" and user_id "%s"',
            'getLpStatusChangedByUsrAndObjId',
            $obj_id,
            $user_id
        ));

        $query_status = 'SELECT status_changed FROM ut_lp_marks ulm ' .
            'WHERE ulm.obj_id = ' . $DIC->database()->quote($obj_id, ilDBConstants::T_INTEGER) . ' ' .
            'AND ulm.usr_id = ' . $DIC->database()->quote($user_id, ilDBConstants::T_INTEGER);

        $result = $DIC->database()->query($query_status);
        $lp_status = $DIC->database()->fetchAll($result);

        if ($lp_status !== [] && array_key_exists('status_changed', $lp_status[0])) {
            $lp_status_changed = (string) $lp_status[0]['status_changed'];
            $this->logger->debug(sprintf(
                'lp_status_changed %s found for (usr, obj) %s, %s (DEBUG: timestamp) %s',
                $lp_status_changed,
                $user_id,
                $obj_id,
                strtotime($lp_status_changed)
            ));

            return strtotime($lp_status_changed);
        }

        $this->logger->debug('no lp_status_changed found');
        return 0;
    }

    /**
     * Get readable role title by role_id
     */
    public function getRoleTitleByRoleId(int $role_id): string
    {
        $roleObj = ilObjectFactory::getInstanceByObjId($role_id, false);
        if (!($roleObj instanceof ilObjRole)) {
            return '';
        }

        $found_num = preg_match('/(member|tutor|admin)/', $roleObj->getTitle(), $matches);
        $role_title = '';
        if ($found_num > 0) {
            switch ($matches[0]) {
                case 'member':
                    $role_title = 'member';
                    break;

                case 'tutor':
                    $role_title = 'tutor';
                    break;

                case 'admin':
                    $role_title = 'administrator';
                    break;

                default:
                    $role_title = $roleObj->getTitle();
                    break;
            }
        }

        return $role_title;
    }

    /**
     * Get role id of parent container object assignment
     */
    public function getParentContainerAssignmentRoleForObjectByRefIdAndUserId(
        int $ref_id = null,
        int $user_id = -1,
        ?string $eventtype = null
    ): int {
        global $DIC;

        if (!isset($ref_id)) {
            return -1;
        }

        $cont_ref_id = $this->getContainerRefIdByObjectRefIdAndTypes($ref_id, [], $eventtype);

        $this->logger->debug(sprintf(
            'called "%s" with ref_id "%s" and user_id "%s"',
            'getParentContainerAssignmentRoleForObjectByRefIdAndUserId',
            $ref_id,
            $user_id
        ));

        $select_assignments = 'SELECT rua.rol_id FROM object_reference oref ' .
            'LEFT JOIN rbac_fa rfa ON rfa.parent = oref.ref_id ' .
            'LEFT JOIN rbac_ua rua ON rua.rol_id = rfa.rol_id ' .
            'WHERE rfa.assign = ' . $DIC->database()->quote('y', ilDBConstants::T_TEXT) . ' ' .
            'AND rua.rol_id IS NOT NULL ' .
            'AND rua.usr_id = ' . $DIC->database()->quote($user_id, ilDBConstants::T_INTEGER) . ' ' .
            'AND oref.ref_id = ' . $DIC->database()->quote($cont_ref_id, ilDBConstants::T_INTEGER);

        $result = $DIC->database()->query($select_assignments);
        $assignments = $DIC->database()->fetchAll($result);

        if ($assignments !== [] && array_key_exists('rol_id', $assignments[0])) {
            $this->logger->debug(sprintf('role_id %s found', $assignments[0]['rol_id']));
            return (int) $assignments[0]['rol_id'];
        }

        $this->logger->debug('no role found');
        return -1;
    }

    /**
     * Get parent container ref_id by matching container types
     * @param list<string> $types
     */
    public function getContainerRefIdByObjectRefIdAndTypes(
        int $ref_id,
        array $types = [],
        ?string $eventtype = null
    ): int {
        if ($types === []) {
            $types = ['crs', 'grp', 'prg'];
        }

        $this->logger->debug(sprintf(
            'called %s with ref_id %s and types: [%s]',
            'getContainerRefIdByObjectRefIdAndTypes',
            $ref_id,
            implode(',', $types)
        ));

        $refObj = ilObjectFactory::getInstanceByRefId($ref_id, false);
        if ($refObj instanceof ilObject && in_array($refObj->getType(), $types, true)) {
            $cont_ref_id = $ref_id;
        } else {
            $cont_ref_id = $this->searchFirstParentRefIdByTypes($ref_id, $types);
            if ($cont_ref_id === 0) {
                if (!isset($eventtype) || $eventtype !== 'toTrash') {
                    global $DIC;
                    $tree = $DIC->repositoryTree();

                    $paths = $tree->getPathFull($ref_id, ROOT_FOLDER_ID);
                    $this->logger->debug(sprintf('searching in path %s', implode(',', $paths)));
                    if ($paths !== []) {
                        foreach (array_reverse($paths) as $path) {
                            $this->logger->debug(sprintf('checking path item %s', $path['id']));
                            $cont_ref_id = $this->searchFirstParentRefIdByTypes((int) $path['id'], $types);
                            if ($cont_ref_id > 0) {
                                break;
                            }
                        }
                    }
                }
            }
        }
        // return -1 if no container was found
        if ($cont_ref_id === 0) {
            $this->logger->debug('no container ref_id found');
            return -1;
        }

        $this->logger->debug(sprintf('container ref_id %s found', $cont_ref_id));
        return $cont_ref_id;
    }

    /**
     * Search the first matching parent ref_id by the given types and the pased child ref_id
     * @param list<string> $types
     */
    private function searchFirstParentRefIdByTypes(int $ref_id, array $types): int
    {
        global $DIC;

        $tree = $DIC->repositoryTree();

        $this->logger->debug(sprintf(
            'called %s with ref_id %s and types: [%s]',
            'searchFirstParentRefIdByTypes',
            $ref_id,
            print_r($types, true)
        ));

        foreach ($types as $type) {
            $parent_ref_id = $tree->checkForParentType($ref_id, $type);
            if ($parent_ref_id > 0) {
                return $parent_ref_id;
            }
        }

        return 0;
    }
}
