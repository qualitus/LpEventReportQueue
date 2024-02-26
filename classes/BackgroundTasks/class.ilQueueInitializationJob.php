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

use ILIAS\BackgroundTasks\Implementation\Bucket\State;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Types\SingleType;
use QU\LERQ\BackgroundTasks\QueueInitializationJobDefinition;
use QU\LERQ\Events\AbstractEvent;
use QU\LERQ\BackgroundTasks\AssignmentCollector;
use QU\LERQ\Helper\EventDataAggregationHelper;
use QU\LERQ\Helper\InitialQueueCollector;
use QU\LERQ\Model\MemberModel;
use QU\LERQ\Model\ObjectModel;
use QU\LERQ\Model\QueueModel;
use QU\LERQ\Model\SettingsModel;
use QU\LERQ\Model\UserModel;

class ilQueueInitializationJob extends AbstractJob
{
    private string $db_table;
    private QueueInitializationJobDefinition $definitions;
    private ilDBInterface $db;
    private AssignmentCollector $collector;
    private ?SettingsModel $settingsModel = null;
    private ?ilDBStatement $prepared_statement = null;

    public function run(array $input, Observer $observer): IntegerValue
    {
        global $DIC;

        // Get plugin object
        $plugin = ilLpEventReportQueuePlugin::getInstance();

        // Get job state definitions and settings keyword (table)
        $this->definitions = new QueueInitializationJobDefinition();
        $this->db_table = $this->definitions::JOB_TABLE;

        $output = new IntegerValue();

        $this->logMessage('Start initial queue collection.');

        // Get collector singleton (always use as singleton because of tree)
        $collector = InitialQueueCollector::singleton();

        // Get / Set initial tast information
        $task_info = $this->getTaskInformations();

        // get EventDataAggregationHelper singleton and settingsModel
        $eventDataAggregator = EventDataAggregationHelper::singleton();
        if (!isset($this->settingsModel)) {
            $this->settingsModel = new SettingsModel();
        }

        // check if task is already running (or already finished / failed) and should be stopped
        if ($this->isTaskRunning($task_info) || $this->isTaskLocked($task_info)) {
            $output->setValue($this->definitions::JOB_RETURN_ALREADY_RUNNING);
            return $output;
        }

        if ($this->isTaskFinished($task_info)) {
            $output->setValue($this->definitions::JOB_RETURN_SUCCESS);
            return $output;
        }

        if ($this->isTaskFailed($task_info)) {
            $output->setValue($this->definitions::JOB_RETURN_FAILED);
            return $output;
        }

        $this->logMessage(
            sprintf(
                'Queue initialization started (Memory Usage: %s)',
                ilUtil::formatSize(memory_get_usage(true), 'long')
            )
        );

        // Notify observer that the script is now running
        $observer->notifyState(State::RUNNING);
        $observer->notifyPercentage($this, 0);

        // Get object selection setting, collect usr data, count base data and update task
        $type_select = $task_info['obj_select'] ?? 'role_assignments';
        $ref_id_determination = $task_info['ref_id_determination'] ?? 'all';
        /** @var null|list<int> $learning_progress */
        $learning_progress = $task_info['learning_progress_status'] ?? null;

        $found = $collector->countBaseDataFromDB($type_select, $learning_progress);

        $this->logMessage(
            sprintf(
                'Determined number of records to be processed (Memory Usage: %s)',
                ilUtil::formatSize(memory_get_usage(true), 'long')
            )
        );

        $this->updateTask([
            'lock' => true, // <- prevents multiple executions at the same time
            'state' => $this->definitions::JOB_STATE_RUNNING,
            'found_items' => $found
        ]);

        // Prepare counting variables
        $processed_count = (int) $task_info['processed_items'];
        $processed = 0;
        $start = max($processed_count, 0);
        $stepcount = 2000;

        // Read all user data
        $user_data = $collector->collectUserDataFromDB($type_select, $learning_progress);

        $this->logMessage(
            sprintf(
                'Collected user data (Memory Usage: %s)',
                ilUtil::formatSize(memory_get_usage(true), 'long')
            )
        );

        /** @var array<int, string> $cached_roles_by_id */
        $cached_role_titles_by_id = [];
        /** @var <string, int> $cached_role_ids_by_crs_id */
        $cached_role_ids_by_crs_id = [];
        /** @var array<int, list<int>> $ref_ids_by_obj_id_cache */
        $ref_ids_by_obj_id_cache = [];
        /** @var array<int, int> $parent_crs_ref_id_by_ref_id_cache */
        $parent_crs_ref_id_by_ref_id_cache = [];
        /** @var array<int, array|null> $crs_data_by_ref_id_cache */
        $crs_data_by_ref_id_cache = [];
        $access = $DIC->access();
        $ref_id_determination = 'first_read';

        while (true) {
            $iterator = $collector->collectBaseDataFromDB($start, $stepcount, $type_select, $learning_progress);

            $this->logMessage(
                sprintf(
                    'Collected event data (Start: %s|Step: %s|Memory Usage: %s)' .
                    ' / Determine cache sizes ($crs_data_by_ref_id_cache: %s|$cached_role_titles_by_id: %s|$parent_crs_ref_id_by_ref_id_cache: %s|$crs_data_by_ref_id_cache: %s)',
                    $start,
                    $stepcount,
                    ilUtil::formatSize(memory_get_usage(true), 'long'),
                    count($cached_role_ids_by_crs_id),
                    count($cached_role_titles_by_id),
                    count($parent_crs_ref_id_by_ref_id_cache),
                    count($crs_data_by_ref_id_cache),
                )
            );

            $has_records = false;
            $i = 0;
            foreach ($iterator as $record) {
                $has_records = true;

                $ref_ids = [];
                if ($type_select === 'role_assignments' || $ref_id_determination === 'first') {
                    $ref_ids = [
                        (int) $record['ref_id']
                    ];
                }

                if ($type_select === 'learning_progress' && $ref_id_determination !== 'first') {
                    $ref_ids_by_obj_id = $ref_ids_by_obj_id_cache[(int) $record['obj_id']] ?? ($ref_ids_by_obj_id_cache[(int) $record['obj_id']] = array_map(
                        'intval',
                        array_values(ilObject::_getAllReferences((int) $record['obj_id']))
                    ));

                    $first = true;
                    $ref_ids = array_filter(
                        $ref_ids_by_obj_id,
                        static function (int $ref_id) use ($record, &$first, $ref_id_determination, $access): bool {
                            if ('all' === $ref_id_determination) {
                                return true;
                            }

                            $has_access = $access->checkAccessOfUser((int) $record['usr_id'], 'read', '', $ref_id);
                            if (!$has_access) {
                                return false;
                            }

                            if ('first_read' === $ref_id_determination && !$first) {
                                return false;
                            }

                            $first = false;
                            return true;
                        }
                    );

                    unset($ref_ids_by_obj_id);
                }

                foreach ($ref_ids as $ref_id) {
                    $this->persistEventData(
                        $collector,
                        $eventDataAggregator,
                        $ref_id,
                        $record,
                        $user_data[$record['usr_id']] ?? [],
                        $cached_role_ids_by_crs_id,
                        $cached_role_titles_by_id,
                        $parent_crs_ref_id_by_ref_id_cache,
                        $crs_data_by_ref_id_cache
                    );
                }

                unset($ref_ids);

                ++$processed;

                if ($i > 0 && $i % 100 === 0) {
                    $this->logMessage(
                        sprintf(
                            '. (Memory Usage: %s)',
                            ilUtil::formatSize(memory_get_usage(true), 'long')
                        )
                    );
                }

                // Update task to know the last obj_id, if the script fails.
                $this->updateTask([
                    'last_item' => $record['obj_id'] . '_' . $record['usr_id'],
                ]);

                ++$i;
            }

            unset($iterator);

            if ($has_records) {
                // Tell the observer that the script is alive
                $observer->heartbeat();
                $start += $stepcount;

                // After $stepcount of data, we update the task progress information
                $this->updateTask([
                    'processed_items' => $processed_count + $processed,
                    'progress' => $this->measureProgress($found, $processed_count + $processed)
                ]);
                $this->logMessage(
                    'Initial Queue collection: ' . $this->measureProgress($found, $processed_count + $processed) . '%.',
                    'debug'
                );
            } else {
                break;
            }
        }

        // After we finished, log the amount of processed events.
        $this->logMessage('Processed ' . $processed . ' events.');

        // Measure progress.
        $progress = $this->measureProgress($found, $processed_count + $processed);

        // Log that the script finished and notify the observer
        $this->logMessage('Finished initial queue collection.');
        $observer->notifyPercentage($this, round($progress));
        $observer->notifyState($progress > 99 ? State::FINISHED : State::ERROR);

        // Update the task information
        $this->updateTask([
            'lock' => $progress <= 99,
            'state' => $progress > 99 ? $this->definitions::JOB_STATE_FINISHED : $this->definitions::JOB_STATE_STOPPED,
            'processed_items' => $processed_count + $processed,
            'progress' => $this->measureProgress($found, $processed_count + $processed),
            'finished_ts' => time()
        ]);

        $this->logMessage(
            sprintf(
                'Finished queue initialization (Memory Usage: %s|Peak Usage: %s)',
                ilUtil::formatSize(memory_get_usage(true), 'long'),
                ilUtil::formatSize(memory_get_peak_usage(true), 'long')
            )
        );

        // Set the Output value on "success" or "stopped" weather the progress is above 99% or not. The 99% is used
        // because of rounding difference, the progress could be something like: 99.7432%
        $output->setValue($progress > 99 ? $this->definitions::JOB_RETURN_SUCCESS : $this->definitions::JOB_RETURN_STOPPED);
        return $output;
    }

    private function getStatement(): ilDBStatement
    {
        global $DIC;

        if ($this->prepared_statement !== null) {
            return $this->prepared_statement;
        }

        return ($this->prepared_statement = $DIC->database()->prepareManip(
            'INSERT INTO `' . AbstractEvent::DB_TABLE . '` 
                (
                    `id`,
                    `timestamp`,
                    `event`,
                    `event_type`,
                    `progress`,
                    `assignment`, 
                    `course_start`,
                    `course_end`,
                    `user_data`,
                    `obj_data`,
                    `mem_data`,
                    `progress_changed`
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_CLOB,
                ilDBConstants::T_CLOB,
                ilDBConstants::T_CLOB,
                ilDBConstants::T_INTEGER
            ]
        ));
    }

    /**
     * @param array<string, scalar|array<string, mixed>> $data Aggregated data from run method
     */
    private function save(array $data): bool
    {
        global $DIC;

        try {
            // Get settingsModel if not available
            if (!isset($this->settingsModel)) {
                $this->settingsModel = new SettingsModel();
            }

            // prepare Queue base
            $queue = new QueueModel();

            // check for object collection setting and ignore the save call, if object type does not match the settings.
            if (array_key_exists('objectdata', $data) && !empty($data['objectdata'])) {
                if (
                    $this->settingsModel->getItem('obj_select')->getValue() !== '*' &&
                    $this->settingsModel->getItem('obj_select')->getValue() != $data['objectdata']['type']
                ) {
                    $this->logMessage('Skipped event because of configuration.');
                    return true;
                }
            }

            $queue->setTimestamp($data['progress_changed'])
                ->setProgress($data['progress'])
                ->setProgressChanged($data['progress_changed'])
                ->setAssignment($data['assignment']);

            // Set learning period data
            if (array_key_exists('lpperiod', $data) && !empty($data['lpperiod'])) {
                /** @var ilDateTime[] $lpp */
                $lpp = $data['lpperiod'];
                if ($lpp['course_start'] instanceof ilDateTime) {
                    $queue->setCourseStart($lpp['course_start']->getUnixTime());
                } else {
                    $queue->setCourseStart($lpp['course_start']);
                }
                if ($lpp['course_end'] instanceof ilDateTime) {
                    $queue->setCourseEnd($lpp['course_end']->getUnixTime());
                } else {
                    $queue->setCourseEnd($lpp['course_end']);
                }
            }

            // Set user data
            $user = new UserModel();
            if ($this->settingsModel->getItem('user_fields') != false && $this->settingsModel->getItem('user_fields')->getValue()) {
                if (array_key_exists('userdata', $data) && !empty($data['userdata'])) {
                    $ud = $data['userdata'];
                    if ($this->settingsModel->getItem('user_id')->getValue()) {
                        $user->setUsrId($ud['user_id']);
                    }
                    if ($this->settingsModel->getItem('login')->getValue()) {
                        $user->setLogin($ud['username']);
                    }
                    if ($this->settingsModel->getItem('firstname')->getValue()) {
                        $user->setFirstname($ud['firstname']);
                    }
                    if ($this->settingsModel->getItem('lastname')->getValue()) {
                        $user->setLastname($ud['lastname']);
                    }
                    if ($this->settingsModel->getItem('title')->getValue()) {
                        $user->setTitle($ud['title']);
                    }
                    if ($this->settingsModel->getItem('gender')->getValue()) {
                        $user->setGender($ud['gender']);
                    }
                    if ($this->settingsModel->getItem('email')->getValue()) {
                        $user->setEmail($ud['email']);
                    }
                    if ($this->settingsModel->getItem('institution')->getValue()) {
                        $user->setInstitution($ud['institution']);
                    }
                    if ($this->settingsModel->getItem('street')->getValue()) {
                        $user->setStreet($ud['street']);
                    }
                    if ($this->settingsModel->getItem('city')->getValue()) {
                        $user->setCity($ud['city']);
                    }
                    if ($this->settingsModel->getItem('country')->getValue()) {
                        $user->setCountry($ud['country']);
                    }
                    if ($this->settingsModel->getItem('phone_office')->getValue()) {
                        $user->setPhoneOffice($ud['phone_office']);
                    }
                    if ($this->settingsModel->getItem('hobby')->getValue()) {
                        $user->setHobby($ud['hobby']);
                    }
                    if ($this->settingsModel->getItem('department')->getValue()) {
                        $user->setDepartment($ud['department']);
                    }
                    if ($this->settingsModel->getItem('phone_home')->getValue()) {
                        $user->setPhoneHome($ud['phone_home']);
                    }
                    if ($this->settingsModel->getItem('phone_mobile')->getValue()) {
                        $user->setPhoneMobile($ud['phone_mobile']);
                    }
                    if ($this->settingsModel->getItem('fax')->getValue()) {
                        $user->setFax($ud['fax']);
                    }
                    if ($this->settingsModel->getItem('referral_comment')->getValue()) {
                        $user->setReferralComment($ud['referral_comment']);
                    }
                    if ($this->settingsModel->getItem('matriculation')->getValue()) {
                        $user->setMatriculation($ud['matriculation']);
                    }
                    if ($this->settingsModel->getItem('active')->getValue()) {
                        $user->setActive($ud['active']);
                    }
                    if ($this->settingsModel->getItem('approval_date')->getValue()) {
                        $user->setApprovalDate($ud['approval_date']);
                    }
                    if ($this->settingsModel->getItem('agree_date')->getValue()) {
                        $user->setAgreeDate($ud['agree_date']);
                    }
                    if ($this->settingsModel->getItem('auth_mode')->getValue()) {
                        $user->setAuthMode($ud['auth_mode']);
                    }
                    if ($this->settingsModel->getItem('ext_account')->getValue()) {
                        $user->setExtAccount($ud['ext_account']);
                    }
                    if ($this->settingsModel->getItem('birthday')->getValue()) {
                        $user->setBirthday($ud['birthday']);
                    }
                    if ($this->settingsModel->getItem('import_id')->getValue()) {
                        $user->setImportId($ud['import_id']);
                    }
                    if (array_key_exists('udfdata', $data) && !empty($data['udfdata'])) {
                        if ($this->settingsModel->getItem('udf_fields')->getValue()) {
                            $user->setUdfData($data['udfdata']);
                        }
                    }
                }
            }
            $queue->setUserData($user);

            // Set object data
            $object = new ObjectModel();
            if (array_key_exists('objectdata', $data) && !empty($data['objectdata'])) {
                $od = $data['objectdata'];

                $object->setTitle($od['title'])
                    ->setId($od['id'])
                    ->setRefId($od['ref_id'])
                    ->setLink($od['link'])
                    ->setType($od['type'])
                    ->setCourseTitle($od['course_title'])
                    ->setCourseId($od['course_id'])
                    ->setCourseRefId($od['course_ref_id']);

            }
            $queue->setObjData($object);

            // Set membership data
            $member = new MemberModel();
            if (array_key_exists('memberdata', $data) && !empty($data['memberdata'])) {
                $md = $data['memberdata'];
                $member->setMemberRole($md['role'])
                    ->setCourseTitle($md['course_title'])
                    ->setCourseId($md['course_id'])
                    ->setCourseRefId($md['course_ref_id']);
            }
            $queue->setMemData($member);

            // Create query for both event types
            $timestamp = $queue->getTimestamp(false);
            $crs_start = $queue->getCourseStart(false);
            $crs_end = $queue->getCourseEnd(false);
            $usr_data = $queue->getUserData()->__toString();
            $obj_data = $queue->getObjData()->__toString();
            $mem_data = $queue->getMemData()->__toString();
            $progress_changed = $queue->getProgressChanged(false);
            if (is_string($progress_changed) && !is_numeric($progress_changed)) {
                $progress_changed = strtotime($progress_changed);
            }

            foreach (['addParticipant', 'updateStatus'] as $event) {
                // Save to database
                $DIC->database()->execute(
                    $this->getStatement(),
                    [
                        $DIC->database()->nextId(AbstractEvent::DB_TABLE),
                        $timestamp,
                        $event,
                        $this->mapEventToType($event),
                        $queue->getProgress(),
                        $queue->getAssignment(),
                        $crs_start,
                        $crs_end,
                        $usr_data,
                        $obj_data,
                        $mem_data,
                        $progress_changed
                    ]
                );
            }

            // Free the space by unsetting $queue
            unset($queue);

            $this->logMessage(
                'initial queue collection INFO: Wrote entry for (user_id, ref_id): ' .
                $data['userdata']['user_id'] . ', ' . $data['objectdata']['ref_id'] . '.',
                'debug'
            );

            // Free the space by unsetting $data
            unset($data);

            return true;
        } catch (Exception $e) {
            $this->logMessage('initial queue collection Error:' . "\n" . $e->getMessage(), 'error');

            // Free the space by unsetting $queue and $data
            unset($queue);

            return false;
        }
    }


    protected function mapEventToType(string $a_event): string
    {
        return ($a_event === 'updateStatus' ? 'lp_event' : ($a_event === 'addParticipant' ? 'member_event' : 'unknown'));
    }

    public function isStateless(): bool
    {
        return true;
    }

    public function getExpectedTimeOfTaskInSeconds(): int
    {
        return 100;
    }

    public function getInputTypes(): array
    {
        return [];
    }

    public function getOutputType(): \ILIAS\BackgroundTasks\Types\Type
    {
        return new SingleType(IntegerValue::class);
    }

    /**
     * @return array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int}
     */
    private function getTaskInformations(): array
    {
        global $DIC;

        $settings = $DIC->settings();

        $task_info = json_decode($settings->get($this->db_table, '{}'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($task_info) || $task_info === []) {
            $task_info = $this->initTask();
        }

        return $task_info;
    }

    /**
     * Initialize the task information
     * @return array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int}
     */
    private function initTask(): array
    {
        global $DIC;

        $settings = $DIC->settings();

        $this->logMessage('Init Task for LpEventQueue Initialization.', 'debug');

        $task_info = [
            'lock' => false,
            'state' => $this->definitions::JOB_STATE_INIT,
            'found_items' => 0,
            'processed_items' => 0,
            'progress' => 0,
            'started_ts' => time(),
            'finished_ts' => null,
            'last_item' => 0
        ];

        $settings->set($this->db_table, json_encode($task_info, JSON_THROW_ON_ERROR));

        return $task_info;
    }

    /***
     * Update task information
     * @param array{}|array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $data
     * @return array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int}
     */
    private function updateTask(array $data): array
    {
        global $DIC;

        $settings = $DIC->settings();

        $task_info = $this->getTaskInformations();

        if (array_key_exists('lock', $data)) {
            $task_info['lock'] = $data['lock'];
        }
        if (array_key_exists('state', $data)) {
            $task_info['state'] = $data['state'];
        }
        if (array_key_exists('found_items', $data)) {
            $task_info['found_items'] = $data['found_items'];
        }
        if (array_key_exists('processed_items', $data)) {
            $task_info['processed_items'] = $data['processed_items'];
        }
        if (array_key_exists('progress', $data)) {
            $task_info['progress'] = $data['progress'];
        }
        if (array_key_exists('finished_ts', $data)) {
            if (is_string($data['finished_ts'])) {
                $data['finished_ts'] = strtotime($data['finished_ts']);
            }
            $task_info['finished_ts'] = $data['finished_ts'];
        }
        if (array_key_exists('last_item', $data)) {
            $task_info['last_item'] = $data['last_item'];
        }


        $this->logMessage('Update data: ' . json_encode($task_info, JSON_THROW_ON_ERROR), 'debug');

        $settings->set($this->db_table, json_encode($task_info, JSON_THROW_ON_ERROR));

        return $task_info;
    }

    private function measureProgress(int $found, int $processed = 0): int
    {
        return (int) round((100 / $found * $processed), 0);
    }

    private function logMessage(string $message, string $type = 'info'): void
    {
        global $DIC;

        $logger = $DIC->logger()->root();

        $m_prefix = '[BackgroundTask][LpEventReportingQueue] ';
        switch ($type) {
            case 'critical':
                $logger->critical($m_prefix . $message);
                break;

            case 'error':
                $logger->error($m_prefix . $message);
                break;

            case 'warning':
                $logger->warning($m_prefix . $message);
                break;

            case 'notice':
                $logger->notice($m_prefix . $message);
                break;

            case 'info':
                $logger->info($m_prefix . $message);
                break;

            case 'debug':
                $logger->debug($m_prefix . $message);
                break;
        }
    }

    /**
     * @param array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function isTaskRunning(array $task_info): bool
    {
        return $task_info['state'] === $this->definitions::JOB_STATE_RUNNING;
    }

    /**
     * @param array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function isTaskStopped(array $task_info): bool
    {
        return $task_info['state'] === $this->definitions::JOB_STATE_STOPPED;
    }

    /**
     * @param array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function isTaskFinished(array $task_info): bool
    {
        return $task_info['state'] === $this->definitions::JOB_STATE_FINISHED;
    }

    /**
     * @param array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function isTaskFailed(array $task_info): bool
    {
        return $task_info['state'] === $this->definitions::JOB_STATE_FAILED;
    }

    /**
     * @param array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function isTaskLocked(array $task_info): bool
    {
        return $task_info['lock'] === true;
    }

    private function isQueueEmpty(): bool
    {
        $query = 'SELECT EXISTS(SELECT 1 FROM ' . AbstractEvent::DB_TABLE . ' LIMIT 1) AS cnt';
        $exists = (bool) ((int) ($this->db->fetchAssoc($this->db->query($query))['cnt'] ?? 0));

        return !$exists;
    }

    private function persistEventData(
        InitialQueueCollector $collector,
        EventDataAggregationHelper $eventDataAggregator,
        int $ref_id,
        array $bd,
        array $user_data,
        array &$cached_role_ids_by_crs_id,
        array &$cached_role_titles_by_id,
        array &$parent_crs_ref_id_by_ref_id_cache,
        array &$crs_data_by_ref_id_cache
    ): void {
        // check if current object is type course
        if ($bd['type'] === 'crs') {
            $crs_ref_id = $ref_id;
        } else {
            // if not type course, try to find a parent course ref_id
            $crs_ref_id = $parent_crs_ref_id_by_ref_id_cache[$ref_id] ?? ($parent_crs_ref_id_by_ref_id_cache[$ref_id] = $collector->findParentCourse($ref_id));
        }

        if ($crs_ref_id === -1) {
            // if object is not a course and no parent course could be found, we use "fail data"
            $course_data = [
                'crs_start' => null,
                'crs_end' => null,
                'title' => '',
                'obj_id' => -1,
                'ref_id' => -1
            ];
        } else {
            // if we've got a course ref_id, collect the course data
            $course_data = $crs_data_by_ref_id_cache[$crs_ref_id] ?? ($crs_data_by_ref_id_cache[$crs_ref_id] = $collector->collectCourseDataByRefId($crs_ref_id));
        }

        if ((int) $bd['rol_id'] === -1) {
            if ($course_data['ref_id'] > 0 || $course_data['obj_id'] > 0) {
                $crs_role_det_id = $course_data['ref_id'] > 0 ? (int) $course_data['ref_id'] : (int) $course_data['obj_id'];
                $is_rerference = $course_data['ref_id'] > 0;
                $cache_key = $crs_role_det_id . '_' . (int) $is_rerference;

                if (!isset($cached_role_ids_by_crs_id[$cache_key])) {
                    $cached_role_ids_by_crs_id[$cache_key] = (new \QU\LERQ\Queue\CaptureRoutines\Routines())->getRoleAssignmentByUserIdAndCourseId(
                        (int) $bd['usr_id'],
                        $crs_role_det_id,
                        $is_rerference
                    );
                }

                $bd['rol_id'] = $cached_role_ids_by_crs_id[$cache_key];
            }
        }

        // Prepare the data array, to write the "events"
        $ud = $user_data;
        $aggregated = [
            'progress' => $eventDataAggregator->getLpStatusRepresentation($bd['status']),
            'progress_changed' => $bd['status_changed'],
            'assignment' => '-',
            'lpperiod' => [
                'course_start' => new ilDate($course_data['crs_start'], IL_CAL_UNIX),
                'course_end' => new ilDate($course_data['crs_end'], IL_CAL_UNIX),
            ],
            'userdata' => [
                'user_id' => $ud['usr_id'],
                'username' => $ud['login'],
                'firstname' => $ud['firstname'],
                'lastname' => $ud['lastname'],
                'title' => $ud['title'],
                'gender' => $ud['gender'],
                'email' => $ud['email'],
                'institution' => $ud['institution'],
                'street' => $ud['street'],
                'city' => $ud['city'],
                'country' => $ud['country'],
                'phone_office' => $ud['phone_office'],
                'hobby' => $ud['hobby'],
                'department' => $ud['department'],
                'phone_home' => $ud['phone_home'],
                'phone_mobile' => $ud['phone_mobile'],
                'fax' => $ud['fax'],
                'referral_comment' => $ud['referral_comment'],
                'matriculation' => $ud['matriculation'],
                'active' => $ud['active'],
                'approval_date' => $ud['approve_date'],
                'agree_date' => $ud['agree_date'],
                'auth_mode' => $ud['auth_mode'],
                'ext_account' => $ud['ext_account'],
                'birthday' => $ud['birthday'],
                'import_id' => $ud['import_id'],
            ],
            'udfdata' => $ud['udfdata'],
            'objectdata' => [
                'title' => $bd['title'],
                'id' => $bd['obj_id'],
                'ref_id' => $ref_id,
                'link' => ilLink::_getStaticLink($ref_id, $bd['type'] ?? ''),
                'type' => $bd['type'],
                'course_title' => $course_data['title'],
                'course_id' => $course_data['obj_id'],
                'course_ref_id' => $course_data['ref_id'],
            ],
            'memberdata' => [
                'role' => (int) $bd['rol_id'] !== -1 ? (int) $bd['rol_id'] : null,
                'course_title' => $course_data['title'],
                'course_id' => $course_data['obj_id'],
                'course_ref_id' => $course_data['ref_id'],
            ]
        ];

        if ((int) $bd['rol_id'] !== -1) {
            $aggregated['assignment'] = $cached_role_titles_by_id[(int) $bd['rol_id']] ?? ($cached_role_titles_by_id[(int) $bd['rol_id']] = $eventDataAggregator->getRoleTitleByRoleId((int) $bd['rol_id']));
        }

        // Save the "events"
        $this->save($aggregated);

        unset($aggregated);
    }
}
