<?php

namespace QU\LERQ\Helper;

/**
 * Class EventDataAggregationHelper
 * @package QU\LERQ\Helper
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class EventDataAggregationHelper
{
	/** @var EventDataAggregationHelper */
	protected static $instance;

	/** @var \ilLogger  */
	protected $logger;

	/**
	 * EventDataAggregationHelper constructor.
	 */
	public function __construct()
	{
		global $DIC;
		$this->logger = $DIC->logger()->root();
	}

	/**
	 * Get singleton
	 *
	 * @return EventDataAggregationHelper
	 */
	public static function singleton()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get learning progress status representation by lp.status
	 *
	 * @param int $status
	 * @return string
	 */
	public function getLpStatusRepresentation($status = 0): string
	{
		$lpStatus = '';
		switch ($status) {
			case \ilLPStatus::LP_STATUS_IN_PROGRESS_NUM:
				$lpStatus = 'in_progress';
				break;
			case \ilLPStatus::LP_STATUS_COMPLETED_NUM:
				$lpStatus = 'completed';
				break;
			case \ilLPStatus::LP_STATUS_FAILED_NUM:
				$lpStatus = 'failed';
				break;
            case \ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM:
            default:
                $lpStatus = 'no_attempted';
                break;
		}
		return $lpStatus;
	}

    /**
     * Get numeric LP status
     *
     * @param int $user_id
     * @param int $obj_id
     * @return int
     */
	public function getLpStatusByUsrAndObjId(int $user_id, int $obj_id): int
    {

        if (!isset($user_id) || !isset($obj_id)) {
            return 0;
        }
        global $DIC;

        $this->logger->debug(sprintf('called "%s" with obj_id "%s" and user_id "%s"',
            'getLpStatusByUsrAndObjId',
            $obj_id,
            $user_id
        ));

        $query_status = 'SELECT status FROM ut_lp_marks ulm ' .
            'WHERE ulm.obj_id = ' . $DIC->database()->quote($obj_id, 'integer') . ' ' .
            'AND ulm.usr_id = ' . $DIC->database()->quote($user_id, 'integer') . ' ';


        $result = $DIC->database()->query($query_status);
        $lp_status = $DIC->database()->fetchAll($result);

        if (!empty($lp_status) && array_key_exists('status', $lp_status[0])) {
            $this->logger->debug(sprintf('lp_status %s found', $lp_status[0]['status']));
            return $lp_status[0]['status'];
        } else {
            $this->logger->debug(sprintf('no lp_status found'));
            return 0;
        }

    }

    /**
     * Get LP status data
     *
     * @param int $user_id
     * @param int $obj_id
     * @return int|array
     */
	public function getLpStatusInfoByUsrAndObjId(int $user_id, int $obj_id)
    {

        if (!isset($user_id) || !isset($obj_id)) {
            return 0;
        }
        global $DIC;

        $this->logger->debug(sprintf('called "%s" with obj_id "%s" and user_id "%s"',
            'getLpStatusByUsrAndObjId',
            $obj_id,
            $user_id
        ));

        $query_status = 'SELECT * FROM ut_lp_marks ulm ' .
            'WHERE ulm.obj_id = ' . $DIC->database()->quote($obj_id, 'integer') . ' ' .
            'AND ulm.usr_id = ' . $DIC->database()->quote($user_id, 'integer') . ' ';


        $result = $DIC->database()->query($query_status);
        $lp_status = $DIC->database()->fetchAll($result);

        if (!empty($lp_status) && array_key_exists('status', $lp_status[0])) {
            $this->logger->debug('lp_status data found');
            return $lp_status[0];
        } else {
            $this->logger->debug(sprintf('no lp_status data found'));
            return 0;
        }

    }

    /**
     * Get LP status last change time
     *
     * @param int $user_id
     * @param int $obj_id
     * @return int
     */
	public function getLpStatusChangedByUsrAndObjId(int $user_id, int $obj_id): int
    {

        if (!isset($user_id) || !isset($obj_id)) {
            return 0;
        }
        global $DIC;

        $this->logger->debug(sprintf('called "%s" with obj_id "%s" and user_id "%s"',
            'getLpStatusChangedByUsrAndObjId',
            $obj_id,
            $user_id
        ));

        $query_status = 'SELECT status_changed FROM ut_lp_marks ulm ' .
            'WHERE ulm.obj_id = ' . $DIC->database()->quote($obj_id, 'integer') . ' ' .
            'AND ulm.usr_id = ' . $DIC->database()->quote($user_id, 'integer') . ' ';


        $result = $DIC->database()->query($query_status);
        $lp_status = $DIC->database()->fetchAll($result);

        if (!empty($lp_status) && array_key_exists('status_changed', $lp_status[0])) {
            $lp_status_changed = (string) $lp_status[0]['status_changed'];
            $this->logger->debug(sprintf('lp_status_changed %s found for (usr, obj) %s, %s (DEBUG: timestamp) %s', $lp_status_changed, $user_id, $obj_id, strtotime($lp_status_changed)));
            return strtotime($lp_status_changed);
        } else {
            $this->logger->debug(sprintf('no lp_status_changed found'));
            return 0;
        }

    }

	/**
	 * Get readable role title by role_id
	 *
	 * @param int $role_id
	 * @return string
	 */
	public function getRoleTitleByRoleId(int $role_id): string
	{
		/** @var \ilObjRole $roleObj */
		$roleObj = \ilObjectFactory::getInstanceByObjId($role_id);

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
	 *
	 * @param int $ref_id
	 * @param int $user_id
	 * @return int
	 */
	public function getParentContainerAssignmentRoleForObjectByRefIdAndUserId(int $ref_id = null, int $user_id = -1, $eventtype = null): int
	{
		if (!isset($ref_id)) {
			return -1;
		}
		global $DIC;
		$cont_ref_id = $this->getContainerRefIdByObjectRefIdAndTypes($ref_id, [], $eventtype);

		$this->logger->debug(sprintf('called "%s" with ref_id "%s" and user_id "%s"',
			'getParentContainerAssignmentRoleForObjectByRefIdAndUserId',
			$ref_id,
			$user_id
		));

		$select_assignments = 'SELECT rua.rol_id FROM object_reference oref ' .
			'LEFT JOIN rbac_fa rfa ON rfa.parent = oref.ref_id ' .
			'LEFT JOIN rbac_ua rua ON rua.rol_id = rfa.rol_id ' .
			'WHERE rfa.assign = "y" ' .
			'AND rua.rol_id IS NOT NULL ' .
			'AND rua.usr_id = ' . $DIC->database()->quote($user_id, 'integer') . ' ';
		$select_assignments .= 'AND oref.ref_id = ' . $DIC->database()->quote($cont_ref_id, 'integer') . ' ';


		$result = $DIC->database()->query($select_assignments);
		$assignments = $DIC->database()->fetchAll($result);

		if (!empty($assignments) && array_key_exists('rol_id', $assignments[0])) {
			$this->logger->debug(sprintf('role_id %s found', $assignments[0]['rol_id']));
			return $assignments[0]['rol_id'];
		} else {
			$this->logger->debug(sprintf('no role found'));
			return -1;
		}

	}

	/**
	 * Get parent container ref_id by matching container types
	 *
	 * @param int $ref_id
	 * @param array $types
	 * @return int
	 */
	public function getContainerRefIdByObjectRefIdAndTypes(int $ref_id, array $types = [], $eventtype = null): int
	{
		if (!isset($type) || empty($type)) {
			$types = ['crs', 'grp', 'prg'];
		}
		$this->logger->debug(sprintf('called %s with ref_id %s and types: [%s]',
			'getContainerRefIdByObjectRefIdAndTypes',
			$ref_id,
			implode(',', $types)
		));

		$refObj = \ilObjectFactory::getInstanceByRefId($ref_id);
		if ($refObj instanceof \ilObject && in_array($refObj->getType(), $types)) {
			$cont_ref_id = $ref_id;

		} else {
			$cont_ref_id = $this->searchFirstParentRefIdByTypes($ref_id, $types);
			if ($cont_ref_id === false || $cont_ref_id === 0) {
			    if (!isset($eventtype) || $eventtype != 'toTrash') {
                    global $DIC;
                    $tree = $DIC->repositoryTree();

                    $paths = $tree->getPathFull($ref_id);
                    $this->logger->debug(sprintf('searching in path %s', $paths));
                    if (is_array($paths) && count($paths) > 0) {
                        foreach (array_reverse($paths) as $path) {
                            $this->logger->debug(sprintf('checking path item %s', $path['id']));
                            $cont_ref_id = $this->searchFirstParentRefIdByTypes($path['id'], $types);

                            if ($cont_ref_id !== false && $cont_ref_id > 0) {
                                break;
                            }
                        }
                    }
                }
			}
		}
		// return -1 if no container was found
		if ($cont_ref_id === false || $cont_ref_id === 0) {
			$this->logger->debug(sprintf('no container ref_id found'));
			return -1;
		}

		$this->logger->debug(sprintf('container ref_id %s found', $cont_ref_id));
		return $cont_ref_id;
	}

	/**
	 * Search the first matching parent by a ref_id
	 *
	 * @param int $ref_id
	 * @param array $types
	 * @return int|bool
	 */
	private function searchFirstParentRefIdByTypes(int $ref_id, array $types)
	{
		global $DIC;
		$tree = $DIC->repositoryTree();
		$this->logger->debug(sprintf('called %s with ref_id %s and types: [%s]',
			'searchFirstParentRefIdByTypes',
			$ref_id,
			$types
		));

		foreach ($types as $type) {
			$parent_type = $tree->checkForParentType($ref_id, $type);
			if ($parent_type !== false && $parent_type > 0) {
				break;
			}
		}

		return $parent_type;
	}

}