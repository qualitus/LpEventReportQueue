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

namespace QU\LERQ\BackgroundTasks;

use ilDBInterface;

class AssignmentCollector
{
    private ilDBInterface $db;
    private int $ass_count = 0;

    public function __construct(ilDBInterface $database)
    {
        $this->db = $database;
    }

    /**
     * Get assignments for user, role, object from database
     * @return array<int, array<int, array{obj_id: int, rol_id: int}>>
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
            $this->db->setLimit($limit);
        }
        $res = $this->db->query($select_assignments);

        $assignments = [];
        while ($data = $this->db->fetchAssoc($res)) {
            if ((int) $data['usr_id'] === 6) {
                continue;
            }

            if (!array_key_exists((int) $data['ref_id'], $assignments)) {
                $assignments[(int) $data['ref_id']] = [];
            }

            $assignments[(int) $data['ref_id']][(int) $data['usr_id']] = [
                'obj_id' => (int) $data['obj_id'],
                'rol_id' => (int) $data['rol_id'],
            ];
        }

        if ($limit === 0) {
            $this->ass_count = $this->countAssignmentItems($assignments);
        }

        return $assignments;
    }

    public function getCountOfAllAssignments(bool $force_new = false): int
    {
        if (!isset($this->ass_count) || $force_new === true) {
            $this->getAssignments(0, 0);
        }

        return $this->ass_count;
    }

    /**
     * Count items from assignment array
     * @param array<int, array<int, array{obj_id: int, rol_id: int}>> $assignments
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
