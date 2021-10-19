<?php

namespace QU\LERQ\BackgroundTasks;

/**
 * Class AssignmentCollector
 * @package QU\LERQ\BackgroundTasks
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class AssignmentCollector
{
	/** @var \ilDBInterface  */
	protected $db;

	/** @var int */
	protected $ass_count;

	/**
	 * AssignmentCollector constructor.
	 * @param \ilDBInterface $database
	 */
	public function __construct(\ilDBInterface $database)
	{
		$this->db = $database;
	}

	/**
	 * Get assignments for user, role, object from database
	 *
	 * @param int $limit		Limit for assignment entries
	 * @param int $start_ref	Ref_id to start with, in case this is ran more then once
	 * @return array
	 */
	public function getAssignments(int $limit = 1000, int $start_ref = 0): array
	{
		$select_assignments = 'SELECT rua.usr_id, oref.ref_id, oref.obj_id, rua.rol_id, ud.type ' .
			'FROM object_reference oref ' .
			'LEFT JOIN rbac_fa rfa ON rfa.parent = oref.ref_id ' .
			'LEFT JOIN rbac_ua rua ON rua.rol_id = rfa.rol_id ' .
			'LEFT JOIN object_data ud ON ud.obj_id = oref.obj_id ' .
			'WHERE rfa.assign = "y" ' .
			'AND rua.rol_id IS NOT NULL ' .
			'AND ud.type NOT IN ("rolf", "role") ' .
			'AND oref.ref_id >= ' . $start_ref . ' ';
		if ($limit > 0) {
			$select_assignments .= 'LIMIT ' . $limit . ' ';
		}
		$res = $this->db->query($select_assignments);

		$assignments = [];
		while ($data = $this->db->fetchAssoc($res))
		{
			if ($data['usr_id'] == 6) {
				continue;
			}
			if (!array_key_exists($data['ref_id'], $assignments)) {
				$assignments[$data['ref_id']] = [];
			}
			$assignments[$data['ref_id']][$data['usr_id']] = [
				'obj_id' => $data['obj_id'],
				'rol_id' => $data['rol_id'],
			];
		}
		if ($limit === 0) {
			$this->ass_count = $this->countAssignmentItems($assignments);
		}

		return $assignments;
	}

	/**
	 * Get the count of assignments in database
	 *
	 * @param bool $force_new	Force to count again from db
	 * @return int
	 */
	public function getCountOfAllAssignments($force_new = false): int
	{
		if (!isset($this->ass_count) || $force_new === true) {
			$this->getAssignments(0, 0);
		}

		return $this->ass_count;
	}

	/**
	 * Count items from assignment array
	 *
	 * @param array $assignments
	 * @return int
	 */
	public function countAssignmentItems(array $assignments): int
	{
		$count = 0;
		foreach ($assignments as $ref_id => $data) {
			$count += count($data);
		}

		return $count;
	}
}