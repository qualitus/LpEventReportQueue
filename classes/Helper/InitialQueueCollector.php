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

class InitialQueueCollector
{
    private static ?InitialQueueCollector $instance = null;

    /** @var array<int, array{0: int, 1: null|string}> */
    private array $tree = [];

    public static function singleton(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Collect Base Data
     * Collects the base data, required to capture all other relevant queue data.
     * @return list<array<string, mixed>>
     */
    public function collectBaseDataFromDB(int $start = 0, int $end = 1000, string $only_type = '*'): array
    {
        global $DIC;

        $result = $DIC->database()->query($this->getBaseDataQuery([
            'rua.usr_id',
            'oref.ref_id',
            'oref.obj_id',
            'rua.rol_id',
            'od.type',
            'ulm.status',
            'ulm.status_changed',
            'od.title',
            'cs.crs_start',
            'cs.crs_end'
        ], 0, false, [$start, $end], ['oref.ref_id', 'ASC'], $only_type));

        return $DIC->database()->fetchAll($result);
    }

    /**
     * Count Base Data
     * Get the count of all rows, that could be collected by collectBaseDataFromDB
     * @see collectBaseDataFromDB
     */
    public function countBaseDataFromDB(string $only_type = '*'): int
    {
        global $DIC;

        $result = $DIC->database()->query($this->getBaseDataQuery([
            'COUNT(rua.usr_id) AS count'
        ], 0, false, [], [], $only_type));

        return (int) ($DIC->database()->fetchAll($result)[0]['count'] ?? 0);
    }

    /**
     * Collect User Data
     * collects all user data, enclosed by the list of user ids from collectBaseDataFromDB
     * @return array<int, array<string, mixed>>
     * @see getBaseDataQuery
     * @see collectBaseDataFromDB
     */
    public function collectUserDataFromDB(): array
    {
        global $DIC;

        $user_id_subq = $this->getBaseDataQuery(['rua.usr_id'], 0, true);

        $query = 'SELECT `ud`.`usr_id`
	,`ud`.`login` ,`ud`.`firstname` ,`ud`.`lastname` ,`ud`.`title` ,`ud`.`gender` ,`ud`.`email` ,`ud`.`institution` 
	,`ud`.`street` ,`ud`.`city` ,`ud`.`country`	,`ud`.`phone_office` ,`ud`.`hobby` ,`ud`.`department` ,`ud`.`phone_home` 
	,`ud`.`phone_mobile` ,`ud`.`fax` ,`ud`.`referral_comment` ,`ud`.`matriculation` ,`ud`.`active` ,`ud`.`approve_date` 
	,`ud`.`agree_date` ,`ud`.`auth_mode` ,`ud`.`ext_account` ,`ud`.`birthday` ,`uod`.`import_id`
FROM `usr_data` `ud`
JOIN `object_data` `uod` ON `uod`.`obj_id` = `ud`.`usr_id`
WHERE `ud`.`usr_id` IN (' . $user_id_subq . ')';
        $result = $DIC->database()->query($query);

        $udf_query = 'SELECT udf.usr_id, udf.field_id, udf.`value`
FROM (
    SELECT usr_id, field_id, `value` FROM udf_text WHERE usr_id IN (' . $user_id_subq . ')
    UNION
    SELECT usr_id, field_id, `value` FROM udf_clob WHERE usr_id IN (' . $user_id_subq . ')
) udf
LEFT JOIN udf_definition udfd
    ON udfd.field_id = udf.field_id
WHERE udfd.visible = 1
ORDER BY udf.usr_id';
        $udf_res = $DIC->database()->query($udf_query);

        $udf_data = [];
        while ($udf_row = $DIC->database()->fetchAssoc($udf_res)) {
            if (!array_key_exists((int) $udf_row['usr_id'], $udf_data)) {
                $udf_data[(int) $udf_row['usr_id']] = [];
            }

            $udf_data[(int) $udf_row['usr_id']]['f_' . $udf_row['field_id']] = (
                $udf_row['value'] ?? ''
            );
        }

        $data = [];
        while ($row = $DIC->database()->fetchAssoc($result)) {
            if (isset($udf_data[(int) $row['usr_id']])) {
                $row['udfdata'] = $udf_data[(int) $row['usr_id']];
            } else {
                $row['udfdata'] = [];
            }

            $data[(int) $row['usr_id']] = $row;
        }

        return $data;
    }

    /**
     * Find parent course
     * Collects the tree, if not already done and walks through it, to find the parent course.
     * @param int $ref_id Ref ID of the current (non-course) object.
     * @return int          Returns the parent Ref ID if a parent course is found. Otherwise -1.
     */
    public function findParentCourse(int $ref_id): int
    {
        global $DIC;

        if (empty($this->tree)) {
            $query = 'SELECT child, parent, `type`
FROM tree tr
LEFT JOIN object_reference obr
	ON tr.parent = obr.ref_id
LEFT JOIN object_data obd
	ON obr.obj_id = obd.obj_id

WHERE tr.depth > 1
AND tr.tree = 1
AND obd.type NOT IN (
	"usrf", "rolf", "adm", "objf", "lngf", "mail", "recf", "cals", "trac", 
	"auth", "assf", "stys", "seas", "extt", "adve", "ps", "nwss", "pdts", 
	"mds", "cmps", "facs", "svyf", "mcts", "tags", "cert", "lrss", "accs",
	"mobs", "file", "qpl", "root", "typ", "usr"
)

ORDER BY tr.child';

            $result = $DIC->database()->query($query);
            while ($row = $DIC->database()->fetchAssoc($result)) {
                $this->tree[(int) $row['child']] = [(int) $row['parent'], $row['type']];
            }
        }

        if (!isset($this->tree[$ref_id])) {
            return -1;
        }

        [$parent, $type] = $this->tree[$ref_id];

        if (!isset($parent, $type)) {
            return -1;
        }

        if ($type !== 'crs') {
            $crs_ref = $this->findParentCourse($parent);
        } else {
            $crs_ref = $parent;
        }

        return $crs_ref;
    }

    /**
     * Collect course data
     * Collect the base data of courses
     * @param int $ref_id Ref ID of the current (course) object.
     * @return null|array<string, mixed>
     */
    public function collectCourseDataByRefId(int $ref_id): ?array
    {
        global $DIC;

        $query = 'SELECT `oref`.`ref_id` ,`oref`.`obj_id` ,`od`.`title`,`cs`.`crs_start` ,`cs`.`crs_end`
        FROM object_reference oref
        LEFT JOIN object_data od 
            ON oref.obj_id = od.obj_id
        INNER JOIN `crs_settings` `cs`
            ON `cs`.`obj_id` = `od`.`obj_id`
        WHERE oref.ref_id = ' . $ref_id . ';';


        $result = $DIC->database()->query($query);
        $data = $DIC->database()->fetchAll($result);

        return $data[0] ?? null;
    }

    /**
     * Create the base data query
     *
     * The query is required to get the correct assignment data for: user <> learning object
     *
     * @param array $field_list List of fields to get.
     * @param int $ref_id Lowest Ref ID to start at.
     * @param bool $distinct Distinct the first field of field list.
     * @param array{0: int, 1: int}|array{} $page Database limit pagination. Array [<int>Start , <int>End]
     * @param array{0: string, 1: string}|array{} $order Query result order. Array [<string>Field , <string>Direction]
     * @param string $only_type Specific object type to get. Default "*" is used top get all.
     * @return string
     */
    private function getBaseDataQuery(
        array $field_list,
        int $ref_id = 0,
        bool $distinct = false,
        array $page = [],
        array $order = [],
        string $only_type = '*'
    ): string {
        $query = 'SELECT ' . ($distinct ? 'DISTINCT ' : '') . implode(',', $field_list) . '
FROM `object_reference` `oref` 
LEFT JOIN `rbac_fa` `rfa` 
    ON `rfa`.`parent` = `oref`.`ref_id` 
LEFT JOIN `rbac_ua` `rua` 
    ON `rua`.`rol_id` = `rfa`.`rol_id` 
LEFT JOIN `object_data` `od` 
    ON `od`.`obj_id` = `oref`.`obj_id` 
LEFT JOIN `ut_lp_marks` `ulm` 
    ON `ulm`.`obj_id` = `oref`.`obj_id`
    AND `ulm`.`usr_id` = `rua`.`usr_id`
LEFT JOIN `crs_settings` `cs`
    ON `od`.`obj_id` = `cs`.`obj_id`
WHERE `rfa`.`assign` = "y" 
    AND `rua`.`rol_id` IS NOT NULL 
    ';
        if ($only_type !== '*') {
            $query .= 'AND `od`.`type` = "' . $only_type . '" ';
        } else {
            $query .= 'AND `od`.`type` NOT IN ("rolf", "role") ';
        }
        $query .= ' 
    AND `oref`.`ref_id` >= ' . $ref_id . '
    AND `rua`.`usr_id` != 6
    AND `rua`.`usr_id` != 0
    AND `oref`.`deleted` IS NULL';
        if (count($order) === 2) {
            $query .= '
            ORDER BY ' . $order[0] . ' ' . $order[1] . ' ';
        }
        if (count($page) === 2) {
            $query .= '
            LIMIT ' . $page[0] . ', ' . $page[1] . ' ';
        }

        return $query;
    }
}
