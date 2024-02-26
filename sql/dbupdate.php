<#1>
<?php
/** @var ilDBInterface $ilDB */
if (!$ilDB->tableExists('lerq_queue')) {
    $ilDB->createTable('lerq_queue', [
        'id' => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ],
        'timestamp' => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true,
            'default' => ''
        ],
        'event' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'event_type' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'progress' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => false,
        ],
        'assignment' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => false,
        ],
        'course_start' => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => false,
            'default' => ''
        ],
        'course_end' => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => false,
            'default' => ''
        ],
        'user_data' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'obj_data' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'mem_data' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
    ]);
    $ilDB->addPrimaryKey('lerq_queue', ['id']);
    $ilDB->createSequence('lerq_queue');
}

if (!$ilDB->tableExists('lerq_provider_register')) {
    $ilDB->createTable('lerq_provider_register', [
        'id' => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ],
        'name' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'namespace' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'path' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'has_overrides' => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ],
        'active_overrides' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => true,
        ],
        'created_at' => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true,
            'default' => ''
        ],
        'updated_at' => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => false,
            'default' => ''
        ],
    ]);
    $ilDB->addPrimaryKey('lerq_provider_register', ['id']);
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
        'type' => ilDBConstants::T_INTEGER,
        'length' => 4,
        'notnull' => true,
    ]);
    $ilDB->addTableColumn('lerq_queue', 'course_start', [
        'type' => ilDBConstants::T_INTEGER,
        'length' => 4,
        'notnull' => false,
    ]);
    $ilDB->addTableColumn('lerq_queue', 'course_end', [
        'type' => ilDBConstants::T_INTEGER,
        'length' => 4,
        'notnull' => false,
    ]);

    /* Migration Step 2 Start */
    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            $ilDB->update(
                'lerq_queue',
                [
                    'timestamp' => [
                        ilDBConstants::T_INTEGER,
                        strtotime($row['timestamp'])
                    ],
                    'course_start' => [
                        ilDBConstants::T_INTEGER,
                        isset($row['course_start']) ? strtotime($row['course_start']) : null
                    ],
                    'course_end' => [
                        ilDBConstants::T_INTEGER,
                        isset($row['course_end']) ? strtotime($row['course_end']) : null
                    ],
                ],
                [
                    'id' => [
                        ilDBConstants::T_INTEGER,
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
            'type' => ilDBConstants::T_TEXT,
            'length' => 255,
            'notnull' => true,
        ],
        'value' => [
            'type' => ilDBConstants::T_TEXT,
            'notnull' => false,
        ],
        'type' => [
            'type' => ilDBConstants::T_TEXT,
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
        if (($key = array_search($ef['keyword'], $fields, true)) !== false) {
            unset($fields[$key]);
        }
    }

    if (count($fields) > 0) {
        foreach ($fields as $field) {
            $ilDB->insert('lerq_settings', [
                'keyword' => [ilDBConstants::T_TEXT, $field],
                'value' => [ilDBConstants::T_TEXT, 1],
                'type' => [ilDBConstants::T_TEXT, 'boolean']
            ]);
        }
    }

    $ilDB->insert('lerq_settings', [
        'keyword' => [ilDBConstants::T_TEXT, 'obj_select'],
        'value' => [ilDBConstants::T_TEXT, '*'],
        'type' => [ilDBConstants::T_TEXT, ilDBConstants::T_TEXT]
    ]);
}
?>
<#5>
<?php
if ($ilDB->tableExists('lerq_queue') && !$ilDB->tableColumnExists('lerq_queue', 'progress_changed')) {
    $ilDB->addTableColumn('lerq_queue', 'progress_changed', [
        'type' => ilDBConstants::T_INTEGER,
        'length' => 4,
        'notnull' => false,
    ]);
}
?>
<#6>
<?php
if ($ilDB->tableExists('lerq_queue') && $ilDB->tableColumnExists('lerq_queue', 'event')) {
    $ilDB->modifyTableColumn('lerq_queue', 'event', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 50,
    ]);
}

if ($ilDB->tableExists('lerq_queue') && $ilDB->tableColumnExists('lerq_queue', 'event_type')) {
    $ilDB->modifyTableColumn('lerq_queue', 'event_type', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 30,
    ]);
}

if ($ilDB->tableExists('lerq_queue') && $ilDB->tableColumnExists('lerq_queue', 'progress')) {
    $ilDB->modifyTableColumn('lerq_queue', 'progress', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 30,
    ]);
}

if ($ilDB->tableExists('lerq_queue') && $ilDB->tableColumnExists('lerq_queue', 'assignment')) {
    $ilDB->modifyTableColumn('lerq_queue', 'assignment', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 255,
    ]);
}
?>
<#7>
<?php
if ($ilDB->tableExists('lerq_settings') && $ilDB->tableColumnExists('lerq_settings', 'type')) {
    $ilDB->modifyTableColumn('lerq_settings', 'type', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 25,
    ]);
}

if ($ilDB->tableExists('lerq_settings') && $ilDB->tableColumnExists('lerq_settings', 'value')) {
    $ilDB->modifyTableColumn('lerq_settings', 'value', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => false,
        'length' => 255,
    ]);
}
?>
<#8>
<?php
if ($ilDB->tableExists('lerq_provider_register') && $ilDB->tableColumnExists('lerq_provider_register', 'name')) {
    $ilDB->modifyTableColumn('lerq_provider_register', 'name', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 255,
    ]);
}

if ($ilDB->tableExists('lerq_provider_register') && $ilDB->tableColumnExists('lerq_provider_register', 'namespace')) {
    $ilDB->modifyTableColumn('lerq_provider_register', 'namespace', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 500,
    ]);
}

if ($ilDB->tableExists('lerq_provider_register') && $ilDB->tableColumnExists('lerq_provider_register', 'path')) {
    $ilDB->modifyTableColumn('lerq_provider_register', 'path', [
        'type' => ilDBConstants::T_TEXT,
        'notnull' => true,
        'length' => 1000,
    ]);
}
?>
<#9>
<?php
if ($ilDB->tableExists('lerq_provider_register') &&
    $ilDB->tableColumnExists('lerq_provider_register', 'name') &&
    $ilDB->tableColumnExists('lerq_provider_register', 'namespace')) {
    $ilDB->addUniqueConstraint('lerq_provider_register', ['name', 'namespace'], 'c1');
}
?>
