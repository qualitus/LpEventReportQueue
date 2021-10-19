<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Services/Cron/classes/class.ilCronHookPlugin.php");

/**
 * Class ilLpEventReportQueuePlugin
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class ilLpEventReportQueuePlugin extends \ilCronHookPlugin
{
	const PLUGIN_ID = "lpeventreportqueue";
	const PLUGIN_NAME = "LpEventReportQueue";
	const PLUGIN_SETTINGS = "qu_crnhk_lerq";
	const PLUGIN_NS = 'QU\LERQ';

	/** @var ilLpEventReportQueuePlugin */
	protected static $instance;

	/** @var \ilSetting */
	protected $settings;

	/** @var array */
	protected $jobs;

	/**
	 * @return void
	 */
	protected function init()
	{
		self::registerAPI();
		$this->jobs = $this->getCronJobInstances();
	}

	/**
	 * @return ilLpEventReportQueuePlugin
	 */
	public static function getInstance()
	{
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return void
	 */
	public static function registerAutoloader()
	{
		global $DIC;

		if(!isset($DIC['autoload.lc.lcautoloader'])) {
			require_once(realpath(dirname(__FILE__)) . '/Autoload/LCAutoloader.php');
			$Autoloader = new LCAutoloader();
			$Autoloader->register();
			$Autoloader->addNamespace('ILIAS\Plugin', '/Customizing/global/plugins');
			$DIC['autoload.lc.lcautoloader'] = $Autoloader;
		}
		$DIC['autoload.lc.lcautoloader']->addNamespace(self::PLUGIN_NS, realpath(dirname(__FILE__)));
	}

	/**
	 * @return void
	 */
	public static function registerAPI()
	{
		global $DIC;

		self::registerAutoloader();
		if(!isset($DIC['qu.lerq.api'])) {
			$api = new \QU\LERQ\API\API();
			$DIC['qu.lerq.api'] = $api;
		}
	}

	/**
	 * ilLpEventReportQueuePlugin constructor.
	 */
	public function __construct() {
		parent::__construct();

		global $DIC;

		$this->db = $DIC->database();
		$this->settings = new ilSetting(self::PLUGIN_SETTINGS);
	}

	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * @return \ilSetting
	 */
	public function getSettings(): ilSetting
	{
		return $this->settings;
	}

	/**
	 * @return array
	 */
	function getCronJobInstances()
	{
		// get array with all jobs
		$this->jobs = [];
		return $this->jobs;
	}

	/**
	 * @param $a_job_id
	 * @return mixed
	 * @throws Exception
	 */
	function getCronJobInstance($a_job_id)
	{
		// get specific job by id
		if(array_key_exists($a_job_id, $this->jobs)) {
			return $this->jobs[$a_job_id];
		}
		\ilUtil::sendFailure('ERROR: Unknown job called: ' . $a_job_id, true);
		return [];
	}

	/**
	 * @return void
	 */
	protected function afterActivation() {
		if ($this->settings->get('lerq_first_start', true) == true) {
			$this->initSettings();
		}
	}

	/**
	 * @return bool
	 */
	protected function beforeUninstall() {
		// Do something
		global $DIC;

		$db = $DIC->database();
		if ($db->sequenceExists('lerq_queue')) {
			$db->dropSequence('lerq_queue');
		}
		if ($db->tableExists('lerq_queue')) {
			$db->dropTable('lerq_queue');
		}
		if ($db->sequenceExists('lerq_provider_register')) {
			$db->dropSequence('lerq_provider_register');
		}
		if ($db->tableExists('lerq_provider_register')) {
			$db->dropTable('lerq_provider_register');
		}
		if ($db->sequenceExists('lerq_settings')) {
			$db->dropSequence('lerq_settings');
		}
		if ($db->tableExists('lerq_settings')) {
			$db->dropTable('lerq_settings');
		}
		$this->settings->delete('lerq_first_start');
        $DIC->settings()->delete('lerq_first_start');
        $this->settings->delete('lerq_bgtask_init');
        $DIC->settings()->delete('lerq_bgtask_init');

		return true;
	}

	/**
	 * @param string $a_component
	 * @param string $a_event
	 * @param array $a_params
	 * @return bool
	 */
	public function handleEvent($a_component, $a_event, $a_params)
	{
	    if (!$this->isActive()) {
	        return true;
        }
        $pl_settings = new \QU\LERQ\Model\SettingsModel();
	    if ( "1" != $pl_settings->getItem('user_fields')->getValue() ) {
	        return true;
        }
		switch($a_component)
		{
			case "Modules/Course":
				switch ($a_event) {
					/*
					 * $a_event: addParticipant
					 * $a_params: ['obj_id', 'usr_id', 'role_id']
					 */
					case 'addParticipant':
						$handler = new \QU\LERQ\Events\MemberEvent();
						$handler->handle_event($a_event, $a_params);
						break;
					/*
					 * $a_event: deleteParticipant
					 * $a_params: ['obj_id', 'usr_id']
					 */
					case 'deleteParticipant':
						$handler = new \QU\LERQ\Events\MemberEvent();
						$handler->handle_event($a_event, $a_params);
						break;
				}
				/*
				 * $a_event: addToWaitingList
				 * $a_params: ['obj_id', 'usr_id']
				 */
				/*
				 * $a_event: create
				 * $a_params: ['object'(ilObjCourse), 'obj_id', 'appointments'(array)]
				 */
				/*
				 * $a_event: delete
				 * $a_params: ['object'(ilObjCourse), 'obj_id', 'appointments'(array)]
				 */
				/*
				 * $a_event: update
				 * $a_params: ['object'(ilObjCourse), 'obj_id', 'appointments'(array)]
				 */
				/*
				 * $a_event: removeFromWaitingList
				 * $a_params: ['obj_id', 'usr_id']
				 */
				break;
			case "Modules/Excercise":
//				$this->debuglog($a_component, $a_event, $a_params);
//				switch ($a_event) {
//					case 'createAssignment':
//						$handler = new \QU\LERQ\Events\MemberEvent();
//						$handler->handle_event($a_event, $a_params);
//						break;
//					case 'deleteAssignment':
//						$handler = new \QU\LERQ\Events\MemberEvent();
//						$handler->handle_event($a_event, $a_params);
//						break;
//					case 'updateAssignment':
//						$handler = new \QU\LERQ\Events\MemberEvent();
//						$handler->handle_event($a_event, $a_params);
//						break;
//				}
				/*
				 * $a_event: createAssignment
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: delete
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: deleteAssignment
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: updateAssignment
				 * $a_params: [] // @Todo
				 */
				break;
			case "Modules/StudyProgramme":
//				$this->debuglog($a_component, $a_event, $a_params);
//				switch ($a_event) {
//					case 'userAssigned':
//						$handler = new \QU\LERQ\Events\MemberEvent();
//						$handler->handle_event($a_event, $a_params);
//						break;
//					case 'userDeassigned':
//						$handler = new \QU\LERQ\Events\MemberEvent();
//						$handler->handle_event($a_event, $a_params);
//						break;
//				}
				/*
				 * $a_event: userAssigned
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: userDeassigned
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: userSuccessful
				 * $a_params: [] // @Todo
				 */
				break;
			case "Services/Object":
				$this->debuglog($a_component, $a_event, $a_params);
				switch ($a_event) {
					/*
					 * $a_event: create
					 * $a_params: ['obj_id', 'obj_type']
					 */
					case 'create':
						// deactivated event for mantis #6878
//						if (array_key_exists('obj_type', $a_params) && in_array($a_params['obj_type'], ['role', 'wiki', 'mob', 'mobs'])) {
//							global $DIC;
//							$type = (
//								$a_params['obj_type'] === 'role' ? 'Role' : (
//									$a_params['obj_type'] === 'wiki' ? 'Wiki' : (
//										$a_params['obj_type'] === 'mob' || $a_params['obj_type'] === 'mobs' ? 'Media Object' :
//											'unknown'
//									)
//								)
//							);
//							$DIC->logger()->root()->info('Skipping event for ' . $type . ' object.');
//							break;
//						}
//						$handler = new \QU\LERQ\Events\ObjectEvent();
//						$handler->handle_event($a_event, $a_params);
						break;
					/*
					 * $a_event: delete
					 * $a_params: ['obj_id', 'ref_id', 'type', 'old_parent_ref_id']
					 */
					// data can not be captured on delete event
//					case 'delete':
//						$handler = new \QU\LERQ\Events\ObjectEvent();
//						$handler->handle_event($a_event, $a_params);
//						break;
					/*
					 * $a_event: update
					 * $a_params: ['obj_id', 'obj_type', 'ref_id']
					 */
					case 'update':
						// deactivated event for mantis #6878
//						$handler = new \QU\LERQ\Events\ObjectEvent();
//						$handler->handle_event($a_event, $a_params);
						break;
					/*
					 * $a_event: toTrash
					 * $a_params: ['obj_id', 'ref_id', 'old_parent_ref_id']
					 */
					case 'toTrash':
						$handler = new \QU\LERQ\Events\ObjectEvent();
						$handler->handle_event($a_event, $a_params);
						break;
					/*
					 * $a_event: undelete
					 * $a_params: ['obj_id', 'ref_id']
					 */
					case 'undelete':
						$handler = new \QU\LERQ\Events\ObjectEvent();
						$handler->handle_event($a_event, $a_params);
						break;
				}
				/*
				 * $a_event: putObjectInTree
				 * $a_params: ['object'(ilObjCourse), 'obj_type', 'obj_id', 'parent_ref_id']
				 */
				/*
				 * $a_event: beforeDeletion
				 * $a_params: ['object'(any object)]
				 */
				break;
			case "Services/Tracking":
				/*
				 * $a_event: updateStatus
				 * $a_params: ['obj_id', 'usr_id', 'status', 'percentage']
				 */
				switch ($a_event) {
					case 'updateStatus':
						$handler = new \QU\LERQ\Events\LearningProgressEvent();
						$handler->handle_event($a_event, $a_params);
						break;
				}
				break;
			case "Services/User":
				$this->debuglog($a_component, $a_event, $a_params);
				/*
				 * $a_event: afterCreate
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: afterUpdate
				 * $a_params: [] // @Todo
				 */
				/*
				 * $a_event: deleteUser
				 * $a_params: [] // @Todo
				 */
				break;
		}

		return true;
	}

	/**
	 * @return void
	 */
	private function initSettings()
	{
	    global $DIC;
		$pl_settings = new \QU\LERQ\Model\SettingsModel();

		$pl_settings
			->addItem('user_fields', true)
			->addItem('user_id', true)
			->addItem('login', true)
			->addItem('firstname', false)
			->addItem('lastname', false)
			->addItem('title', false)
			->addItem('gender', false)
			->addItem('email', true)
			->addItem('institution', false)
			->addItem('street', false)
			->addItem('city', false)
			->addItem('country', false)
			->addItem('phone_office', false)
			->addItem('hobby', false)
			->addItem('department', false)
			->addItem('phone_home', false)
			->addItem('phone_mobile', false)
			->addItem('fax', false)
			->addItem('referral_comment', false)
			->addItem('matriculation', false)
			->addItem('active', false)
			->addItem('approval_date', false)
			->addItem('agree_date', false)
			->addItem('auth_mode', false)
			->addItem('ext_account', true)
			->addItem('birthday', false)
			->addItem('import_id', true)
			->addItem('udf_fields', false)
			->addItem('obj_select', '*');

		$this->settings->set('lerq_first_start', (int) false);

        $task_info = [
            'lock' => false,
            'state' => 'not started',
            'found_items' => 0,
            'processed_items' => 0,
            'progress' => 0,
            'started_ts' => strtotime('now'),
            'finished_ts' => null,
            'last_item' => 0,
        ];
		$DIC->settings()->set('lerq_bgtask_init', json_encode($task_info));
	}

	/**
	 * @param $a_component
	 * @param $a_event
	 * @param $a_params
	 * @return void
	 */
	private function debuglog($a_component, $a_event, $a_params)
	{
		global $DIC;
		$dumper = new \QU\LERQ\Helper\TVarDumper();
		$DIC->logger()->root()->debug(implode(' -> ', [
			var_export($a_component, true),
			var_export($a_event, true),
			$dumper::dump($a_params, 2),
		]));
	}

}