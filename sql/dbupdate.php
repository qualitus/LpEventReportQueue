<#1>
<?php
/** @var ilDBInterface $ilDB */
if (!$ilDB->tableExists('lerq_queue')) {
    $ilDB->createTable('lerq_queue', [
        'id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ],
        'timestamp' => [
            'type' => 'timestamp',
            'notnull' => true,
            'default' => ''
        ],
        'event' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'event_type' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'progress' => [
            'type' => 'text',
            'notnull' => false,
        ],
        'assignment' => [
            'type' => 'text',
            'notnull' => false,
        ],
        'course_start' => [
            'type' => 'timestamp',
            'notnull' => false,
            'default' => ''
        ],
        'course_end' => [
            'type' => 'timestamp',
            'notnull' => false,
            'default' => ''
        ],
        'user_data' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'obj_data' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'mem_data' => [
            'type' => 'text',
            'notnull' => true,
        ],
    ]);
    $ilDB->addPrimaryKey('lerq_queue', array('id'));
    $ilDB->createSequence('lerq_queue');
}

if (!$ilDB->tableExists('lerq_provider_register')) {
    $ilDB->createTable('lerq_provider_register', [
        'id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ],
        'name' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'namespace' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'path' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'has_overrides' => [
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ],
        'active_overrides' => [
            'type' => 'text',
            'notnull' => true,
        ],
        'created_at' => [
            'type' => 'timestamp',
            'notnull' => true,
            'default' => ''
        ],
        'updated_at' => [
            'type' => 'timestamp',
            'notnull' => false,
            'default' => ''
        ],
    ]);
    $ilDB->addPrimaryKey('lerq_provider_register', array('id'));
    $ilDB->createSequence('lerq_provider_register');
}
?>
<#2>
<?php
if ($ilDB->tableExists('lerq_queue')) {
    /* Migration Step 1 Start */
    $queue = [];
    if (
        $ilDB->tableColumnExists('lerq_queue', 'timestamp') &&
        $ilDB->tableColumnExists('lerq_queue', 'course_start') &&
        $ilDB->tableColumnExists('lerq_queue', 'course_end')
    ) {
        $query = 'SELECT `id`, `timestamp`, `course_start`, `course_end`  FROM `lerq_queue`;';
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($res)) {
            $queue[$row['id']] = [
                'timestamp' => $row['timestamp'],
                'course_start' => $row['course_start'],
                'course_end' => $row['course_end'],
            ];
        }
        /* Migration Step 1 End */

        // drop columns with wrong datatype
        $ilDB->dropTableColumn('lerq_queue', 'timestamp');
        $ilDB->dropTableColumn('lerq_queue', 'course_start');
        $ilDB->dropTableColumn('lerq_queue', 'course_end');
    }

    $ilDB->addTableColumn('lerq_queue', 'timestamp', [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true,
    ]);
    $ilDB->addTableColumn('lerq_queue', 'course_start', [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false,
    ]);
    $ilDB->addTableColumn('lerq_queue', 'course_end', [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false,
    ]);

    /* Migration Step 2 Start */
    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            $ilDB->update(
                'lerq_queue',
                [
                    "timestamp" => [
                        "integer",
                        strtotime($row['timestamp'])
                    ],
                    "course_start" => [
                        "integer",
                        isset($row['course_start']) ? strtotime($row['course_start']) : null
                    ],
                    "course_end" => [
                        "integer",
                        isset($row['course_end']) ? strtotime($row['course_end']) : null
                    ],
                ],
                [
                    "id" => [
                        "integer",
                        $id
                    ],
                ]
            );
        }
    }
    /* Migration Step 2 End */
}
?>
<#3>
<?php
if (!$ilDB->tableExists('lerq_settings')) {
    $ilDB->createTable('lerq_settings', [
        'keyword' => [
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ],
        'value' => [
            'type' => 'text',
            'notnull' => false,
        ],
        'type' => [
            'type' => 'text',
            'notnull' => true,
        ],
    ]);

    $ilDB->addPrimaryKey('lerq_settings', array('keyword'));
    $ilDB->createSequence('lerq_settings');
}
?>
<#4>
<?php
if ($ilDB->tableExists('lerq_settings')) {
    $fields = [
        'user_fields',
        'user_id',
        'login',
        'firstname',
        'lastname',
        'title',
        'gender',
        'email',
        'institution',
        'street',
        'city',
        'country',
        'phone_office',
        'hobby',
        'department',
        'phone_home',
        'phone_mobile',
        'fax',
        'referral_comment',
        'matriculation',
        'active',
        'approval_date',
        'agree_date',
        'auth_mode',
        'ext_account',
        'birthday',
        'import_id',
        'udf_fields',
    ];

    $select = 'SELECT keyword from lerq_settings';
    $res_select = $ilDB->query($select);
    $existing_fields = $ilDB->fetchAll($res_select);

    foreach ($existing_fields as $ef) {
        if (($key = array_search($ef['keyword'], $fields)) !== false) {
            unset($fields[$key]);
        }
    }

    if (count($fields) > 0) {
        foreach ($fields as $field) {
            $ilDB->insert('lerq_settings', [
                'keyword' => ['text', $field],
                'value' => ['text', 1],
                'type' => ['text', 'boolean']
            ]);
        }
    }

    $ilDB->insert('lerq_settings', [
        'keyword' => ['text', 'obj_select'],
        'value' => ['text', '*'],
        'type' => ['text', 'text']
    ]);
}
?>
<#5>
<?php
if ($ilDB->tableExists('lerq_queue')) {
    if (!$ilDB->tableColumnExists('lerq_queue', 'progress_changed')) {
        $ilDB->addTableColumn('lerq_queue', 'progress_changed', [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
        ]);
    }
}
?>