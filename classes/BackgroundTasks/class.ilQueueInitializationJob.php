<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

use \ILIAS\BackgroundTasks\Implementation\Bucket\State;
use \ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use \ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use \ILIAS\BackgroundTasks\Observer;
use \ILIAS\BackgroundTasks\Types\SingleType;
use \QU\LERQ\BackgroundTasks\QueueInitializationJobDefinition;
use \QU\LERQ\Events\AbstractEvent;
use \QU\LERQ\BackgroundTasks\AssignmentCollector;
use \QU\LERQ\Helper\EventDataAggregationHelper;
use \QU\LERQ\Helper\InitialQueueCollector;
use \QU\LERQ\Model\MemberModel;
use \QU\LERQ\Model\ObjectModel;
use \QU\LERQ\Model\QueueModel;
use \QU\LERQ\Model\SettingsModel;
use \QU\LERQ\Model\UserModel;

/**
 * Class ilQueueInitializationJob
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class ilQueueInitializationJob extends AbstractJob
{
    /*
     * info: last test: 08:32:29 - 10:19:04 (01:36:35 h)
     * info: found data: 83595
     * info: captured data: 167190
     * info: progress: 100%
     * info: udf data: complete
     * info: types: only courses
     */

    /*
     * info: last test: 15:51:50 - 18:27:55 (03:36:05)
     * info: found data: 120216
     * info: captured data: 240432
     * info: progress: 100%
     * info: udf data: complete
     * info: types: crs, sess, frm, grp
     */

	/** @var string */
	protected $db_table;

	/** @var QueueInitializationJobDefinition */
	protected $definitions;

	/** @var \ilDB */
	protected $db;

	/** @var AssignmentCollector */
	protected $collector;
	
	/** @var SettingsModel */
	private $settingsModel;

	/**
	 * @param array $input
	 * @param Observer $observer
	 * @return IntegerValue
	 * @throws \ILIAS\BackgroundTasks\Exceptions\InvalidArgumentException
	 */
	public function run(array $input, Observer $observer): IntegerValue
	{
	    // Get plugin object
		\ilPluginAdmin::getPluginObject(
			"Services",
			"Cron",
			"crnhk",
			"LpEventReportQueue"
		);

		// Get job state definitions and settings keyword (table)
        $this->definitions = new QueueInitializationJobDefinition();
        $this->db_table = $this->definitions::JOB_TABLE;

        $output = new IntegerValue();

        $this->logMessage('Start initial queue collection.');

        // Get collector singleton (always use as singleton because of tree)
        $collector = InitialQueueCollector::singleton();

        // Get / Set initial tast information
        $task_info = $this->getTaskInformations();
        if(empty($task_info)) {
            $this->initTask();
        }

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

        // Notify observer that the script is now running
        $observer->notifyState(State::RUNNING);
        $observer->notifyPercentage($this, 0);

        // Get object selection setting, collect usr data, count base data and update task
        $type_select = $this->settingsModel->getItem('obj_select')->getValue();
        $user_data = $collector->collectUserDataFromDB();
        $found = $collector->countBaseDataFromDB($type_select);
        $this->updateTask([
            'lock' => true, // <- prevents multiple executions at the same time
            'state' => $this->definitions::JOB_STATE_RUNNING,
            'found_items' => $found,
        ]);

        // Prepare counting variables
        $processed_count = (int)$task_info['processed_items'];
        $processed = 0;
        $start = ($processed_count > 0 ? $processed_count : 0);
        $stepcount = 2000;

        // Process data (for each $stepcount amount of data)
        while (count($base_data = $collector->collectBaseDataFromDB($start, $stepcount, $type_select)) > 0) {

            foreach ($base_data as $bd) {

                // check if current object is type course
                if ($bd['type'] == 'crs') {
                    $crs_ref_id = $bd['ref_id'];
                } else {
                    // if not type course, try to find a parent course ref_id
                    $crs_ref_id = $collector->findParentCourse($bd['ref_id']);
                }
                if ($crs_ref_id == -1) {
                    // if object is not a course and no parent course could be found, we use "fail data"
                    $course_data = [
                        'crs_start' => NULL,
                        'crs_end' => NULL,
                        'title' => '',
                        'obj_id' => -1,
                    ];
                } else {
                    // if we've got a course ref_id, collect the course data
                    $course_data = $collector->collectCourseDataByRefId($crs_ref_id);
                }

                // Prepare the data array, to write the "events"
                $ud = $user_data[$bd['usr_id']];
                $aggregated = [
                    'progress' => $eventDataAggregator->getLpStatusRepresentation($bd['status']),
                    'progress_changed' => $bd['status_changed'],
                    'assignment' => $eventDataAggregator->getRoleTitleByRoleId($bd['rol_id']),
                    'lpperiod' => [
                        'course_start' => new \ilDate($course_data['crs_start'], IL_CAL_UNIX),
                        'course_end' => new \ilDate($course_data['crs_end'], IL_CAL_UNIX),
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
                        'matriculation' => $ud['matricualtion'],
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
                        'ref_id' => $bd['ref_id'],
                        'link' => \ilLink::_getStaticLink($bd['ref_id'], $bd['type']),
                        'type' => $bd['type'],
                        'course_title' => $course_data['title'],
                        'course_id' => $course_data['obj_id'],
                        'course_ref_id' => $course_data['ref_id'],
                    ],
                    'memberdata' => [
                        'role' => $bd['rol_id'],
                        'course_title' => $course_data['title'],
                        'course_id' => $course_data['obj_id'],
                        'course_ref_id' => $course_data['ref_id'],
                    ],
                ];

                // Save the "events"
                if ($this->save($aggregated)) {
                    $processed++;
                }

                // Update task to know the last ref_id, if the script fails.
                $this->updateTask([
                    'last_item' => $bd['ref_id'],
                ]);
            }

            // Tell the observer that the script is alive
            $observer->heartbeat();
            $start += $stepcount;

            // After $stepcount of data, we update the task progress information
            $this->updateTask([
                'processed_items' => $processed_count + $processed,
                'progress' => $this->measureProgress($found, $processed_count + $processed),
            ]);
            $this->logMessage('Initial Queue collection: ' .
                $this->measureProgress($found, $processed_count + $processed) . '%.', 'debug');
        }

        // After we finished, log the amount of processed events.
        $this->logMessage('Processed ' . ($processed*2) . ' events.');

        // Measure progress.
        $progress = $this->measureProgress($found, $processed_count + $processed);

        // Log that the script finished and notify the observer
        $this->logMessage('Finished initial queue collection.');
        $observer->notifyPercentage($this, round($progress));
        $observer->notifyState(($progress > 99 ? State::FINISHED : State::ERROR));

        // Update the task information
        $this->updateTask([
            'lock' => ($progress > 99 ? false : true),
            'state' => ($progress > 99 ? $this->definitions::JOB_STATE_FINISHED : $this->definitions::JOB_STATE_STOPPED),
            'processed_items' => $processed_count + $processed,
            'progress' => $this->measureProgress($found, $processed_count + $processed),
            'finished_ts' => strtotime('now'),
        ]);

        // Set the Output value on "success" or "stopped" weather the progress is above 99% or not. The 99% is used
        // because of rounding difference, the progress could be something like: 99.7432%
        $output->setValue(($progress > 99 ? $this->definitions::JOB_RETURN_SUCCESS : $this->definitions::JOB_RETURN_STOPPED));
        return $output;

	}

    /**
     * @param array $data   Aggregated data from run method
     * @return bool
     */
    protected function save(array $data)
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
                /**
                 * @var \ilDateTime[] $lpp
                 */
                $lpp = $data['lpperiod'];
                if ($lpp['course_start'] instanceof \ilDateTime) {
                    $queue->setCourseStart($lpp['course_start']->getUnixTime());
                } else {
                    $queue->setCourseStart($lpp['course_start']);
                }
                if ($lpp['course_end'] instanceof \ilDateTime) {
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
                    if ($this->settingsModel->getItem('user_id')->getValue())
                        $user->setUsrId($ud['user_id']);
                    if ($this->settingsModel->getItem('login')->getValue())
                        $user->setLogin($ud['username']);
                    if ($this->settingsModel->getItem('firstname')->getValue())
                        $user->setFirstname($ud['firstname']);
                    if ($this->settingsModel->getItem('lastname')->getValue())
                        $user->setLastname($ud['lastname']);
                    if ($this->settingsModel->getItem('title')->getValue())
                        $user->setTitle($ud['title']);
                    if ($this->settingsModel->getItem('gender')->getValue())
                        $user->setGender($ud['gender']);
                    if ($this->settingsModel->getItem('email')->getValue())
                        $user->setEmail($ud['email']);
                    if ($this->settingsModel->getItem('institution')->getValue())
                        $user->setInstitution($ud['institution']);
                    if ($this->settingsModel->getItem('street')->getValue())
                        $user->setStreet($ud['street']);
                    if ($this->settingsModel->getItem('city')->getValue())
                        $user->setCity($ud['city']);
                    if ($this->settingsModel->getItem('country')->getValue())
                        $user->setCountry($ud['country']);
                    if ($this->settingsModel->getItem('phone_office')->getValue())
                        $user->setPhoneOffice($ud['phone_office']);
                    if ($this->settingsModel->getItem('hobby')->getValue())
                        $user->setHobby($ud['hobby']);
                    if ($this->settingsModel->getItem('department')->getValue())
                        $user->setDepartment($ud['department']);
                    if ($this->settingsModel->getItem('phone_home')->getValue())
                        $user->setPhoneHome($ud['phone_home']);
                    if ($this->settingsModel->getItem('phone_mobile')->getValue())
                        $user->setPhoneMobile($ud['phone_mobile']);
                    if ($this->settingsModel->getItem('fax')->getValue())
                        $user->setFax($ud['phone_fax']);
                    if ($this->settingsModel->getItem('referral_comment')->getValue())
                        $user->setReferralComment($ud['referral_comment']);
                    if ($this->settingsModel->getItem('matriculation')->getValue())
                        $user->setMatriculation($ud['matriculation']);
                    if ($this->settingsModel->getItem('active')->getValue())
                        $user->setActive($ud['active']);
                    if ($this->settingsModel->getItem('approval_date')->getValue())
                        $user->setApprovalDate($ud['approval_date']);
                    if ($this->settingsModel->getItem('agree_date')->getValue())
                        $user->setAgreeDate($ud['agree_date']);
                    if ($this->settingsModel->getItem('auth_mode')->getValue())
                        $user->setAuthMode($ud['auth_mode']);
                    if ($this->settingsModel->getItem('ext_account')->getValue())
                        $user->setExtAccount($ud['ext_account']);
                    if ($this->settingsModel->getItem('birthday')->getValue())
                        $user->setBirthday($ud['birthday']);
                    if ($this->settingsModel->getItem('import_id')->getValue())
                        $user->setImportId($ud['import_id']);
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
            $insert = '';
            foreach (['addParticipant', 'updateStatus'] as $event) {
                $insert .= 'INSERT INTO `' . AbstractEvent::DB_TABLE . '` 
                (`id`, `timestamp`, `event`, `event_type`, `progress`, `assignment`, 
                `course_start`, `course_end`, `user_data`, `obj_data`, `mem_data`, `progress_changed`) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s); 
                ';

                $types = [
                    'integer',
                    'integer',
                    'text',
                    'text',
                    'text',
                    'text',
                    'integer',
                    'integer',
                    'text',
                    'text',
                    'text',
                    'integer',
                ];

                $values = [
                    $DIC->database()->nextId(AbstractEvent::DB_TABLE),
                    $queue->getTimestamp(false),
                    $event,
                    $this->mapEventToType($event),
                    $queue->getProgress(),
                    $queue->getAssignment(),
                    $queue->getCourseStart(false),
                    $queue->getCourseEnd(false),
                    $queue->getUserData()->__toString(),
                    $queue->getObjData()->__toString(),
                    $queue->getMemData()->__toString(),
                    $queue->getProgressChanged(false),
                ];

                $quoted_values = array();
                foreach ($types as $k => $t)
                {
                    $quoted_values[] = $DIC->database()->quote( $values[$k], $t );
                }
                $insert = vsprintf( $insert, $quoted_values );
            }

            // Save to database
            $DIC->database()->manipulateF(
                $insert,
                $types,
                $values
            );

            // Free the space by unsetting $queue
            unset($queue);

            $this->logMessage(
                'initial queue collection INFO: Wrote entry for (user_id, ref_id): ' .
                $data['userdata']['user_id'] . ', ' . $data['objectdata']['ref_id'] . '.',
                'debug');

            // Free the space by unsetting $data
            unset($data);

            return true;

        } catch (\Exception $e) {
            $this->logMessage('initial queue collection Error:' . "\n" . $e->getMessage(), 'error');

            // Free the space by unsetting $queue and $data
            unset($queue);
            unset($data);

            return false;
        }
    }


    /**
     * Map event name to event type
     *
     * @param string $a_event
     * @return string
     */
    protected function mapEventToType(string $a_event): string
    {
        return ($a_event == "updateStatus" ? "lp_event" : ( $a_event == "addParticipant" ? "member_event" : "unknown"));
    }

	/**
	 * @inheritDoc
	 */
	public function isStateless()
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getExpectedTimeOfTaskInSeconds()
	{
		return 100;
	}

	/**
	 * @inheritDoc
	 */
	public function getInputTypes()
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getOutputType()
	{
		return new SingleType(IntegerValue::class);
	}

	/**
     * Get the task information
     *
	 * @return array
	 */
	public function getTaskInformations(): array
	{
		global $DIC;

		$settings = $DIC->settings();

		$task_info = json_decode($settings->get($this->db_table, '{}'), true);
		if (empty($task_info)) {
			$task_info = $this->initTask();
		}

		return $task_info;
	}

	/**
     * Initialize the task information
     *
	 * @return array
	 */
	protected function initTask(): array
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
			'started_ts' => strtotime('now'),
			'finished_ts' => null,
			'last_item' => 0,
		];

		$settings->set($this->db_table, json_encode($task_info));
		return $task_info;
	}

	/***
     * Update task information
     *
	 * @param array $data
	 * @return array
	 */
	protected function updateTask(array $data): array
	{
		global $DIC;
		$settings = $DIC->settings();


		$task_info = $this->getTaskInformations();

		if(array_key_exists('lock', $data)) {
			$task_info['lock'] = $data['lock'];
		}
		if(array_key_exists('state', $data)) {
			$task_info['state'] = $data['state'];
		}
		if(array_key_exists('found_items', $data)) {
			$task_info['found_items'] = $data['found_items'];
		}
		if(array_key_exists('processed_items', $data)) {
			$task_info['processed_items'] = $data['processed_items'];
		}
		if(array_key_exists('progress', $data)) {
			$task_info['progress'] = $data['progress'];
		}
		if(array_key_exists('finished_ts', $data)) {
			if(is_string($data['finished_ts'])) {
				$data['finished_ts'] = strtotime($data['finished_ts']);
			}
			$task_info['finished_ts'] = $data['finished_ts'];
		}
		if(array_key_exists('last_item', $data)) {
			$task_info['last_item'] = $data['last_item'];
		}


		$this->logMessage('Update data: ' . json_encode($task_info), 'debug');

		$settings->set($this->db_table, json_encode($task_info));
		return $task_info;
	}

	/**
     * Measure progress
     *
	 * @param int $found
	 * @param int $processed
	 * @return float
	 */
	protected function measureProgress(int $found, int $processed = 0): float
	{
		return (float)(100 / $found * $processed);
	}

	/**
     * Write message to log
     *
	 * @param string $message
	 * @param string $type
	 * @return void
	 */
	protected function logMessage(string $message, string $type = 'info')
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
	 * @param $task_info
	 * @return bool
	 */
	protected function isTaskRunning($task_info): bool
	{
		return $task_info['state'] === $this->definitions::JOB_STATE_RUNNING;
	}

	/**
	 * @param $task_info
	 * @return bool
	 */
	protected function isTaskStopped($task_info): bool
	{
		return $task_info['state'] === $this->definitions::JOB_STATE_STOPPED;
	}

	/**
	 * @param $task_info
	 * @return bool
	 */
	protected function isTaskFinished($task_info): bool
	{
		return $task_info['state'] === $this->definitions::JOB_STATE_FINISHED;
	}

	/**
	 * @param $task_info
	 * @return bool
	 */
	protected function isTaskFailed($task_info): bool
	{
		return $task_info['state'] === $this->definitions::JOB_STATE_FAILED;
	}

	/**
	 * @param $task_info
	 * @return bool
	 */
	protected function isTaskLocked($task_info): bool
	{
		return $task_info['lock'] === true;
	}

	/**
	 * @return bool
	 */
	private function isQueueEmpty(): bool
	{
		$query = 'SELECT * FROM ' . AbstractEvent::DB_TABLE . ' LIMIT 1';
		$res = $this->db->query($query);
//		$queue = $this->db->fetchAll($res);

		return ($res->numRows() == 0);
	}

}