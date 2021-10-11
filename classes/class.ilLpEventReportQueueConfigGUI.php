<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
include_once("./Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/BackgroundTasks/class.ilQueueInitialization.php");
include_once("./Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/BackgroundTasks/class.ilQueueInitializationJob.php");

use \ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use \QU\LERQ\BackgroundTasks\QueueInitializationJobDefinition;
use \QU\LERQ\Events\AbstractEvent;

/**
 * Class ilLpEventReportQueueConfigGUI
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class ilLpEventReportQueueConfigGUI extends ilPluginConfigGUI
{
	/** @var ilLpEventReportQueuePlugin */
	private $plugin;

	/** @var \ilCtrl */
	protected $ctrl;

	/** @var \ilLanguage */
	protected $lng;

	/** @var \ilTemplate */
	protected $tpl;

	/** @var \ilTabsGUI */
	protected $tabs;

	/** @var \ilSetting */
	protected $settings;

	/** @var \ILIAS\DI\BackgroundTaskServices */
	protected $backgroundTasks = null;

	/** @var string */
	protected $active_tab;

	/**
	 * @return void
	 */
	public function construct()
	{
		global $DIC;

		$this->plugin = ilLpEventReportQueuePlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->tpl = $DIC["tpl"];
		$this->tabs = $DIC->tabs();
		$this->settings = $DIC->settings();
		if (null === $this->backgroundTasks) {
			$this->backgroundTasks = $DIC->backgroundTasks();
		}
	}

	/**
	 * @param $cmd
	 * @return void
	 */
	function performCommand($cmd)
	{
		$this->construct();
		$next_class = $this->ctrl->getNextClass($this);
		$this->setTabs();

		switch ($next_class) {
			default:
				switch ($cmd) {
					case "configure":
						$this->tabs->activateTab('configure');
						$this->configure();
						break;
					case "initialization":
						$this->tabs->activateTab('initialization');
						$this->initialization();
						break;
					default:
						$cmd .= 'Cmd';
						$this->$cmd();
						break;
				}
				break;
		}
	}

	/**
	 * @return array
	 */
	public function getTabs(): array
	{
		return [
			0 => [
				'id' => 'configure',
				'txt' => $this->plugin->txt('configuration'),
				'cmd' => 'configure',
			],
			1 => [
				'id' => 'initialization',
				'txt' => $this->plugin->txt('queue_initialization'),
				'cmd' => 'initialization',
			]
		];
	}

	/**
	 * @return void
	 */
	protected function setTabs()
	{
		if (!empty($this->getTabs())) {
			foreach ($this->getTabs() as $tab) {
				$this->tabs->addTab($tab['id'], $tab['txt'], $this->ctrl->getLinkTarget($this, $tab['cmd']));
			}
		}
	}

	/**
	 * @return void
	 */
	public function configure()
	{
		$form = $this->getConfigurationForm();
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * @return void
	 */
	public function initialization()
	{
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt('queue_initialization'));

		$task_info = json_decode($this->settings->get(QueueInitializationJobDefinition::JOB_TABLE, '{}'), true);

		if(!$this->wasInitializationStarted($task_info)) {
            // initialization was NOT started yet
			$ne = new \ilNonEditableValueGUI('', 'start_initialization_by_click_first');
			$ne->setValue($this->plugin->txt('start_initialization_by_click_first'));
			$ne->setInfo(sprintf($this->plugin->txt('start_initialization_by_click_info'), $this->plugin->txt("start_initialization")));
			$form->addItem($ne);

			$form->addCommandButton("startInitialization", $this->plugin->txt("start_initialization"));

		} else if($this->canInitializationStart($task_info)) {
            // initialization is NOT in state RUNNING, FINISHED or STARTED
			$ne = new \ilNonEditableValueGUI('', 'start_initialization_by_click');
			$ne->setValue($this->plugin->txt('start_initialization_by_click'));
			$ne->setInfo(sprintf($this->plugin->txt('start_initialization_by_click_info'), $this->plugin->txt("start_initialization")));
			$form->addItem($ne);

			$form->addCommandButton("startInitialization", $this->plugin->txt("start_initialization"));

		} else if($this->hasInitializationFailed($task_info) || $this->hasInitializationFinished($task_info)) {
            // initialization has failed or is finished
			$ne = new \ilNonEditableValueGUI('', 'show_initialization_status');
			$ne->setValue($this->plugin->txt('show_initialization_status'));
			$ne->setInfo(sprintf($this->plugin->txt('show_initialization_status_info'), $task_info['state']));
			$form->addItem($ne);

		} else if ($this->isInitializationRunning($task_info)) {
            // initialization is currently running
            $ne = new \ilNonEditableValueGUI('', 'show_initialization_running');
            $ne->setValue($this->plugin->txt('show_initialization_running'));
            $ne->setInfo(sprintf($this->plugin->txt('show_initialization_running_info'), $task_info['state']));
            $form->addItem($ne);
        }

        if($this->hasInitializationFailed($task_info) || $this->hasInitializationFinished($task_info)) {
            global $DIC;
            if ($DIC->user()->getId() == 6) {
                $form->addCommandButton("resetQueue", $this->lng->txt("reset"));
            }
        }

		$form->setFormAction($this->ctrl->getFormAction($this));
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	public function getConfigurationForm()
	{
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt('configuration'));

		$settings = new \QU\LERQ\Model\SettingsModel();
		if (!empty($settings->getAll())) {
			$se = new \ilFormSectionHeaderGUI();
			$se->setTitle($this->plugin->txt('user_data'));
			$form->addItem($se);

			$cb = new \ilCheckboxInputGUI($this->plugin->txt('user_fields'), 'user_fields');
			$cb->setChecked($settings->getItem('user_fields')->getValue());

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('user_id'), 'user_id');
			$cbs->setChecked($settings->getItem('user_id')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('login'), 'login');
			$cbs->setChecked($settings->getItem('login')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('firstname'), 'firstname');
			$cbs->setChecked($settings->getItem('firstname')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('lastname'), 'lastname');
			$cbs->setChecked($settings->getItem('lastname')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('title'), 'title');
			$cbs->setChecked($settings->getItem('title')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('gender'), 'gender');
			$cbs->setChecked($settings->getItem('gender')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('email'), 'email');
			$cbs->setChecked($settings->getItem('email')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('institution'), 'institution');
			$cbs->setChecked($settings->getItem('institution')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('street'), 'street');
			$cbs->setChecked($settings->getItem('street')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('city'), 'city');
			$cbs->setChecked($settings->getItem('city')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('country'), 'country');
			$cbs->setChecked($settings->getItem('country')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('phone_office'), 'phone_office');
			$cbs->setChecked($settings->getItem('phone_office')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('hobby'), 'hobby');
			$cbs->setChecked($settings->getItem('hobby')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('department'), 'department');
			$cbs->setChecked($settings->getItem('department')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('phone_home'), 'phone_home');
			$cbs->setChecked($settings->getItem('phone_home')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('phone_mobile'), 'phone_mobile');
			$cbs->setChecked($settings->getItem('phone_mobile')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('fax'), 'fax');
			$cbs->setChecked($settings->getItem('fax')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('referral_comment'), 'referral_comment');
			$cbs->setChecked($settings->getItem('referral_comment')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('matriculation'), 'matriculation');
			$cbs->setChecked($settings->getItem('matriculation')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('active'), 'active');
			$cbs->setChecked($settings->getItem('active')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('approval_date'), 'approval_date');
			$cbs->setChecked($settings->getItem('approval_date')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('agree_date'), 'agree_date');
			$cbs->setChecked($settings->getItem('agree_date')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('auth_mode'), 'auth_mode');
			$cbs->setChecked($settings->getItem('auth_mode')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('ext_account'), 'ext_account');
			$cbs->setChecked($settings->getItem('ext_account')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('birthday'), 'birthday');
			$cbs->setChecked($settings->getItem('birthday')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('import_id'), 'import_id');
			$cbs->setChecked($settings->getItem('import_id')->getValue());
			$cb->addSubItem($cbs);

			$cbs = new \ilCheckboxInputGUI($this->plugin->txt('udf_fields'), 'udf_fields');
			$cbs->setChecked($settings->getItem('udf_fields')->getValue());
			$cb->addSubItem($cbs);

			$form->addItem($cb);

			$se = new \ilFormSectionHeaderGUI();
			$se->setTitle($this->plugin->txt('object_data'));
			$form->addItem($se);

			$si = new \ilSelectInputGUI($this->plugin->txt('obj_select'), 'obj_select');
			$si->setOptions([
				'*' => $this->plugin->txt('obj_all'),
				'crs' => $this->plugin->txt('obj_only_course'),
			]);
			$si->setValue($settings->getItem('obj_select')->getValue());
			$form->addItem($si);

		}

		$form->addCommandButton("save", $this->plugin->txt("save"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	/**
	 * @return void
	 */
	public function saveCmd()
	{
		// @Todo implement switches at AbstractEvent::save
		$form = $this->getConfigurationForm();
		$settings = new \QU\LERQ\Model\SettingsModel();

		if ($form->checkInput()) {
			// save...
			/** @var \QU\LERQ\Model\SettingsItemModel $setting */
			foreach ($settings->getAll() as $keyword => $setting) {
				if ($form->getInput($keyword)) {
					$settings->__set($keyword, $form->getInput($keyword));
				} else {
					$settings->__set($keyword, false);
				}
			}
			$settings->save();

			ilUtil::sendSuccess($this->plugin->txt("saving_invoked"), true);
			$this->ctrl->redirect($this, "configure");

		} else {
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHtml());
		}
	}

	/**
	 * @return string
	 */
	public function startInitializationCmd()
	{
		global $DIC;

		$factory = $this->backgroundTasks->taskFactory();
		$taskManager = $this->backgroundTasks->taskManager();

		$bucket = new BasicBucket();
		$bucket->setUserId($DIC->user()->getId());
		$task = $factory->createTask(ilQueueInitializationJob::class);

		$interaction = ilQueueInitialization::class;
		$queueinit_interaction = $factory->createTask($interaction, [
			$task
		]);

		$bucket->setTask($queueinit_interaction);
		$bucket->setTitle($this->plugin->txt('queue_initialization'));
		$bucket->setDescription($this->plugin->txt('queue_initialization_info'));

		$taskManager->run($bucket);

		\ilUtil::sendInfo($this->plugin->txt('queue_initialization_confirm_started'), true);
		$this->ctrl->redirect($this, 'configure');
		return;

	}

	/**
	 * @return void
	 */
	public function resetQueueCmd()
	{
		$this->plugin->deactivate();

		global $DIC;
		$DIC->database()->manipulate('DELETE FROM ' . AbstractEvent::DB_TABLE . ' WHERE true;');
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

		$this->plugin->activate();

		\ilUtil::sendInfo($this->plugin->txt('queue_reset_confirm'), true);
		$this->ctrl->redirect($this, 'configure');
		return;
	}

	/**
	 * @param array $task_info
	 * @return bool
	 */
	public function wasInitializationStarted($task_info = []): bool
	{
		return (
			!empty($task_info) &&
			$task_info['state'] !== QueueInitializationJobDefinition::JOB_STATE_INIT
		);
	}

	/**
	 * @param array $task_info
	 * @return bool
	 */
	public function canInitializationStart($task_info = []): bool
	{
		return (
			empty($task_info) ||
			!in_array($task_info['state'], [
				QueueInitializationJobDefinition::JOB_STATE_FINISHED,
				QueueInitializationJobDefinition::JOB_STATE_RUNNING,
				QueueInitializationJobDefinition::JOB_STATE_STARTED
			]));
	}

	/**
	 * @param array $task_info
	 * @return bool
	 */
	public function isInitializationRunning($task_info = []): bool
	{
		return (
			!empty($task_info) &&
            $task_info['state'] !== QueueInitializationJobDefinition::JOB_STATE_RUNNING
        );
	}

	/**
	 * @param array $task_info
	 * @return bool
	 */
	public function hasInitializationFailed($task_info = []): bool
	{
		return $task_info['state'] === QueueInitializationJobDefinition::JOB_STATE_FAILED;
	}

	/**
	 * @param array $task_info
	 * @return bool
	 */
	public function hasInitializationFinished($task_info = []): bool
	{
		return $task_info['state'] === QueueInitializationJobDefinition::JOB_STATE_FINISHED;
	}

	/**
	 * @return string
	 */
	private function getActiveTab()
	{
		return $this->active_tab;
	}

}