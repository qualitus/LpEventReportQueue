<?php

namespace QU\LERQ\Helper;

/**
 * Class InitialQueueCollector
 * @package QU\LERQ\Helper
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class InitialQueueCollector
{
    /** @var InitialQueueCollector */
    protected static $instance;

    /** @var array  */
    private $tree = [];

    /**
     * @return InitialQueueCollector
     */
    public static function singleton()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Collect Base Data
     *
     * Collects the base data, required to capture all other relevant queue data.
     *
     * @param int $start            LIMIT start
     * @param int $end              LIMIT step
     * @param string $only_type     Specific object type to get. Default "*" is used top get all.
     * @return array
     */
    public function collectBaseDataFromDB($start = 0, $end = 1000, $only_type = '*'): array
    {
        global $DIC;

        $result = $DIC->database()->query($this->getBaseDataQuery([
            'rua.usr_id', 'oref.ref_id', 'oref.obj_id', 'rua.rol_id', 'od.type', 'ulm.status', 'ulm.status_changed'
            ,'od.title' ,'cs.crs_start' ,'cs.crs_end'
        ], 0, false, [$start, $end], ['oref.ref_id', 'ASC'], $only_type));
        return $DIC->database()->fetchAll($result);
    }

    /**
     * Count Base Data
     *
     * Get the count of all rows, that could be collected by collectBaseDataFromDB
     * @see collectBaseDataFromDB
     *
     * @param string $only_type     Specific object type to get. Default "*" is used top get all.
     * @return array
     */
    public function countBaseDataFromDB($only_type = '*'): int
    {
        global $DIC;

        $result = $DIC->database()->query($this->getBaseDataQuery([
            'COUNT(rua.usr_id) as count'
        ], 0, false, [], [], $only_type));
        return (int) $DIC->database()->fetchAll($result)[0]['count'];
    }

    /**
     * Collect User Data
     *
     * collects all user data, enclosed by the list of user ids from collectBaseDataFromDB
     * @see collectBaseDataFromDB
     * @see getBaseDataQuery
     *
     * @return array
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
        while($udf_row = $DIC->database()->fetchAssoc($udf_res)) {
            if (!array_key_exists($udf_row['usr_id'], $udf_data)) {
                $udf_data[$udf_row['usr_id']] = [];
            }
            $udf_data[$udf_row['usr_id']]["f_" . $udf_row['field_id']] = (
                isset($udf_row['value']) ? $udf_row['value'] : ""
            );
        }

        $data = [];
        while($row = $DIC->database()->fetchAssoc($result)) {

            if (array_key_exists($row['usr_id'], $udf_data)) {
                $row['udfdata'] = $udf_data[$row['usr_id']];
            }
            $data[$row['usr_id']] = $row;
        }

        return $data;
    }

    /**
     * Find parent course
     *
     * Collects the tree, if not already done and walks through it, to find the parent course.
     *
     * @param int $ref_id   Ref ID of the current (non-course) object.
     * @return int          Returns the parent Ref ID if a parent course is found. Otherwise -1.
     */
    public function findParentCourse(int $ref_id)
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
            while($row = $DIC->database()->fetchAssoc($result)) {
                $this->tree[$row['child']] = [$row['parent'], $row['type']];
            }
        }

        list($parent, $type) = $this->tree[$ref_id];
        if (!isset($parent) || !isset($type)) {
            return -1;
        }
        if ($type != 'crs') {
            $crs_ref = $this->findParentCourse((int)$parent);
        } else {
            $crs_ref = $parent;
        }

        return $crs_ref;
    }

    /**
     * Collect course data
     *
     * Collect the base data of courses
     *
     * @param int $ref_id   Ref ID of the current (course) object.
     * @return array
     */
    public function collectCourseDataByRefId(int $ref_id): array
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

        return $data[0];
    }

    /**
     * Create the base data query
     *
     * The query is required to get the correct assignment data for: user <> learning object
     *
     * @param array $field_list     List of fields to get.
     * @param int $ref_id           Lowest Ref ID to start at.
     * @param bool $distinct        Distinct the first field of field list.
     * @param array $page           Database limit pagination. Array [<int>Start , <int>End]
     * @param array $order          Query result order. Array [<string>Field , <string>Direction]
     * @param string $only_type     Specific object type to get. Default "*" is used top get all.
     * @return string
     */
    public function getBaseDataQuery(array $field_list, int $ref_id = 0, $distinct = false, $page = [], $order = [], $only_type = '*'): string
    {
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
    ' ;
        if ($only_type != '*') {
            $query .= 'AND `od`.`type` = "' . $only_type . '" ';
        } else {
            $query .= 'AND `od`.`type` NOT IN ("rolf", "role") ';
        }
        $query .= ' 
    AND `oref`.`ref_id` >= ' . $ref_id . '
    AND `rua`.`usr_id` != 6
    AND `rua`.`usr_id` != 0
    AND `oref`.`deleted` IS NULL';
        if (!empty($order)) {
            $query .= '
            ORDER BY ' . $order[0] . ' ' . $order[1] . ' ';
        }
        if (!empty($page)) {
            $query .= '
            LIMIT ' . $page[0] . ', ' . $page[1] . ' ';
        }

        return $query;
    }

}