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

use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use QU\LERQ\API\ProviderTable;
use QU\LERQ\API\ProviderTableProvider;
use QU\LERQ\BackgroundTasks\AbstractJobDefinition;
use QU\LERQ\BackgroundTasks\QueueInitializationJobDefinition;
use QU\LERQ\Events\AbstractEvent;
use QU\LERQ\Queue\Protocol\ProtocolTable;
use QU\LERQ\Queue\Protocol\ProtocolTableProvider;

/**
 * @ilCtrl_IsCalledBy ilLpEventReportQueueConfigGUI: ilObjComponentSettingsGUI
 */
class ilLpEventReportQueueConfigGUI extends ilPluginConfigGUI
{
    private const ROOT_USER_ID = 6;

    private ilLpEventReportQueuePlugin $plugin;
    private ilCtrlInterface $ctrl;
    private ilLanguage $lng;
    private ilGlobalTemplateInterface $tpl;
    private ilTabsGUI $tabs;
    private ilSetting $settings;
    private \ILIAS\BackgroundTasks\BackgroundTaskServices $backgroundTasks;
    private \ILIAS\DI\UIServices $uiServices;
    private string $active_tab = '';


    private function construct(): void
    {
        global $DIC;

        $this->plugin = ilLpEventReportQueuePlugin::getInstance();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        $this->settings = $DIC->settings();
        $this->uiServices = $DIC->ui();
        $this->backgroundTasks = $DIC->backgroundTasks();
    }

    public function performCommand(string $cmd): void
    {
        $this->construct();
        $next_class = $this->ctrl->getNextClass($this);
        $this->setTabs();

        switch ($next_class) {
            default:
                switch ($cmd) {
                    case 'configure':
                        $this->tabs->activateTab('configure');
                        $this->configure();
                        break;

                    case 'initialization':
                        $this->tabs->activateTab('initialization');
                        $this->initialization();
                        break;

                    case 'applyProtocolFilter':
                        $this->tabs->activateTab('showProtocol');
                        $this->applyProtocolFilter();
                        break;

                    case 'resetProtocolFilter':
                        $this->tabs->activateTab('showProtocol');
                        $this->resetProtocolFilter();
                        break;

                    case 'showProtocol':
                        $this->tabs->activateTab('showProtocol');
                        $this->showProtocol();
                        break;

                    case 'showProviders':
                        $this->tabs->activateTab('showProviders');
                        $this->showProviders();
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
     * @return array<int, array{'id': string, 'txt': string, 'cmd': string}>
     */
    public function getTabs(): array
    {
        $i = 0;

        return [
            $i++ => [
                'id' => 'configure',
                'txt' => $this->plugin->txt('configuration'),
                'cmd' => 'configure',
            ],
            $i++ => [
                'id' => 'showProtocol',
                'txt' => $this->plugin->txt('queue_protocol'),
                'cmd' => 'showProtocol',
            ],
            $i++ => [
                'id' => 'showProviders',
                'txt' => $this->plugin->txt('queue_providers'),
                'cmd' => 'showProviders',
            ],
            $i++ => [
                'id' => 'initialization',
                'txt' => $this->plugin->txt('queue_initialization'),
                'cmd' => 'initialization',
            ]
        ];
    }

    private function setTabs(): void
    {
        if (!empty($this->getTabs())) {
            foreach ($this->getTabs() as $tab) {
                $this->tabs->addTab($tab['id'], $tab['txt'], $this->ctrl->getLinkTarget($this, $tab['cmd']));
            }
        }
    }

    private function configure(): void
    {
        $form = $this->getConfigurationForm();
        $this->tpl->setContent($form->getHTML());
    }

    private function initialization(): void
    {
        global $DIC;

        $form = new ilPropertyFormGUI();
        $form->setTitle($this->plugin->txt('queue_initialization'));

        $task_info = json_decode(
            $this->settings->get(QueueInitializationJobDefinition::JOB_TABLE, '{}'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!$this->wasInitializationStarted($task_info)) {
            // initialization was NOT started yet
            $ne = new ilNonEditableValueGUI('', 'start_initialization_by_click_first');
            $ne->setValue($this->plugin->txt('start_initialization_by_click_first'));
            $ne->setInfo(sprintf(
                $this->plugin->txt('start_initialization_by_click_info'),
                $this->plugin->txt('start_initialization')
            ));
            $form->addItem($ne);

            $se = new ilFormSectionHeaderGUI();
            $se->setTitle($this->plugin->txt('object_data'));
            $form->addItem($se);

            $os = new ilRadioGroupInputGUI($this->plugin->txt('obj_select'), 'obj_select');
            $lpd = new ilRadioOption($this->plugin->txt('obj_select_type_learning_progress'), 'learning_progress');
            $rid = new ilRadioGroupInputGUI($this->plugin->txt('obj_select_type_ref_id_determination'), 'ref_id_determination');
            $rid->addOption(new ilRadioOption($this->plugin->txt('obj_select_type_ref_id_all'), 'all'));
            $rid->addOption(new ilRadioOption($this->plugin->txt('obj_select_type_ref_id_all_read'), 'all_read'));
            $rid->addOption(new ilRadioOption($this->plugin->txt('obj_select_type_ref_id_first'), 'first'));
            $rid->addOption(new ilRadioOption($this->plugin->txt('obj_select_type_ref_id_first_read'), 'first_read'));
            $rid->setValue('all');
            $lpd->addSubItem($rid);
            $rad = new ilRadioOption($this->plugin->txt('obj_select_type_role_assignments'), 'role_assignments');
            $os->addOption($lpd);
            $os->addOption($rad);
            $os->setValue('learning_progress');
            $form->addItem($os);

            $form->addCommandButton('startInitialization', $this->plugin->txt('start_initialization'));

        } elseif ($this->canInitializationStart($task_info)) {
            // initialization is NOT in state RUNNING, FINISHED or STARTED
            $ne = new ilNonEditableValueGUI('', 'start_initialization_by_click');
            $ne->setValue($this->plugin->txt('start_initialization_by_click'));
            $ne->setInfo(sprintf(
                $this->plugin->txt('start_initialization_by_click_info'),
                $this->plugin->txt('start_initialization')
            ));
            $form->addItem($ne);

            $form->addCommandButton('startInitialization', $this->plugin->txt('start_initialization'));

        } elseif ($this->hasInitializationFailed($task_info) || $this->hasInitializationFinished($task_info)) {
            // initialization has failed or is finished
            $ne = new ilNonEditableValueGUI('', 'show_initialization_status');
            $ne->setValue($this->plugin->txt('show_initialization_status'));
            $ne->setInfo(sprintf($this->plugin->txt('show_initialization_status_info'), $task_info['state']));
            $form->addItem($ne);

        } elseif ($this->isInitializationRunning($task_info)) {
            // initialization is currently running
            $ne = new ilNonEditableValueGUI('', 'show_initialization_running');
            $ne->setValue($this->plugin->txt('show_initialization_running'));
            $ne->setInfo(sprintf(
                $this->plugin->txt('show_initialization_running_info'),
                $task_info['state']
            ));
            $form->addItem($ne);
        }

        if ($this->hasInitializationFailed($task_info) || $this->hasInitializationFinished($task_info)) {
            if ($DIC->user()->getId() === self::ROOT_USER_ID) {
                $form->addCommandButton('resetQueue', $this->lng->txt('reset'));
            }
        }

        $form->setFormAction($this->ctrl->getFormAction($this));
        $this->tpl->setContent($form->getHTML());
    }

    private function getConfigurationForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->plugin->txt('configuration'));

        $settings = new QU\LERQ\Model\SettingsModel();
        if (!empty($settings->getAll())) {
            $se = new ilFormSectionHeaderGUI();
            $se->setTitle($this->plugin->txt('user_data'));
            $form->addItem($se);

            $cb = new ilCheckboxInputGUI($this->plugin->txt('user_fields'), 'user_fields');
            $cb->setValue('1');
            $cb->setChecked($settings->getItem('user_fields')->getValue());

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('user_id'), 'user_id');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('user_id')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('login'), 'login');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('login')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('firstname'), 'firstname');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('firstname')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('lastname'), 'lastname');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('lastname')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('title'), 'title');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('title')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('gender'), 'gender');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('gender')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('email'), 'email');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('email')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('institution'), 'institution');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('institution')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('street'), 'street');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('street')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('city'), 'city');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('city')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('country'), 'country');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('country')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('phone_office'), 'phone_office');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('phone_office')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('hobby'), 'hobby');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('hobby')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('department'), 'department');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('department')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('phone_home'), 'phone_home');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('phone_home')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('phone_mobile'), 'phone_mobile');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('phone_mobile')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('fax'), 'fax');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('fax')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('referral_comment'), 'referral_comment');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('referral_comment')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('matriculation'), 'matriculation');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('matriculation')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('active'), 'active');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('active')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('approval_date'), 'approval_date');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('approval_date')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('agree_date'), 'agree_date');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('agree_date')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('auth_mode'), 'auth_mode');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('auth_mode')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('ext_account'), 'ext_account');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('ext_account')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('birthday'), 'birthday');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('birthday')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('import_id'), 'import_id');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('import_id')->getValue());
            $cb->addSubItem($cbs);

            $cbs = new ilCheckboxInputGUI($this->plugin->txt('udf_fields'), 'udf_fields');
            $cbs->setValue('1');
            $cbs->setChecked($settings->getItem('udf_fields')->getValue());
            $cb->addSubItem($cbs);

            $form->addItem($cb);

            $se = new ilFormSectionHeaderGUI();
            $se->setTitle($this->plugin->txt('object_data'));
            $form->addItem($se);

            $si = new ilSelectInputGUI($this->plugin->txt('obj_select'), 'obj_select');
            $si->setOptions([
                '*' => $this->plugin->txt('obj_all'),
                'crs' => $this->plugin->txt('obj_only_course'),
            ]);
            $si->setValue($settings->getItem('obj_select')->getValue());
            $form->addItem($si);

        }

        $form->addCommandButton('save', $this->plugin->txt('save'));
        $form->setFormAction($this->ctrl->getFormAction($this));

        return $form;
    }

    public function saveCmd(): void
    {
        // @Todo implement switches at AbstractEvent::save
        $form = $this->getConfigurationForm();
        $settings = new QU\LERQ\Model\SettingsModel();

        if ($form->checkInput()) {
            // save...
            /** @var \QU\LERQ\Model\SettingsItemModel $setting */
            foreach (array_keys($settings->getAll()) as $keyword) {
                if ($form->getInput($keyword)) {
                    $settings->__set($keyword, $form->getInput($keyword));
                } else {
                    $settings->__set($keyword, false);
                }
            }
            $settings->save();

            $this->tpl->setOnScreenMessage('success', $this->plugin->txt('saving_invoked'), true);
            $this->ctrl->redirect($this, 'configure');
        }

        $form->setValuesByPost();
        $this->tpl->setContent($form->getHtml());
    }

    private function startInitializationCmd(): void
    {
        global $DIC;

        $factory = $this->backgroundTasks->taskFactory();
        $taskManager = $this->backgroundTasks->taskManager();

        $task_info = [
            'lock' => false,
            'state' => \QU\LERQ\BackgroundTasks\AbstractJobDefinition::JOB_STATE_INIT,
            'found_items' => 0,
            'processed_items' => 0,
            'progress' => 0,
            'started_ts' => time(),
            'finished_ts' => null,
            'last_item' => 0,
            'obj_select' => $DIC->http()->wrapper()->post()->retrieve('obj_select', $DIC->refinery()->kindlyTo()->string()),
            'ref_id_determination' => $DIC->http()->wrapper()->post()->retrieve(
                'ref_id_determination',
                $DIC->refinery()->byTrying([$DIC->refinery()->kindlyTo()->string(), $DIC->refinery()->always('all')])
            ),
        ];
        $DIC->settings()->set('lerq_bgtask_init', json_encode($task_info, JSON_THROW_ON_ERROR));

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

        $this->tpl->setOnScreenMessage('info', $this->plugin->txt('queue_initialization_confirm_started'), true);
        $this->ctrl->redirect($this, 'configure');
    }

    private function resetQueueCmd(): void
    {
        global $DIC;

        if ($DIC->user()->getId() !== self::ROOT_USER_ID) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('msg_no_permission'), true);
            $this->ctrl->redirect($this, 'configure');
        }

        $this->plugin->deactivate();

        $DIC->database()->manipulate('DELETE FROM ' . AbstractEvent::DB_TABLE . ' WHERE true;');
        $task_info = [
            'lock' => false,
            'state' => 'not started',
            'found_items' => 0,
            'processed_items' => 0,
            'progress' => 0,
            'started_ts' => time(),
            'finished_ts' => null,
            'last_item' => 0
        ];
        $DIC->settings()->set('lerq_bgtask_init', json_encode($task_info, JSON_THROW_ON_ERROR));

        $this->plugin->activate();

        $this->tpl->setOnScreenMessage('info', $this->plugin->txt('queue_reset_confirm'), true);
        $this->ctrl->redirect($this, 'configure');
    }

    private function getProtocolTable(): ProtocolTable
    {
        return new ProtocolTable(
            $this,
            $this->plugin,
            $this->uiServices,
            'showProtocol'
        );
    }

    private function applyProtocolFilter(): void
    {
        $table = $this->getProtocolTable();
        $table->resetOffset();
        $table->writeFilterToSession();

        $this->showProtocol();
    }

    private function resetProtocolFilter(): void
    {
        $table = $this->getProtocolTable();
        $table->resetOffset();
        $table->resetFilter();

        $this->showProtocol();
    }

    private function showProtocol(): void
    {
        $table = $this->getProtocolTable()
            ->withProvider(new ProtocolTableProvider($GLOBALS['DIC']->database()))
            ->populate();

        $this->tpl->setContent($table->getHtml());
    }

    private function showProviders(): void
    {
        $table = (new ProviderTable(
            $this,
            $this->plugin,
            $this->uiServices,
            'showProviders'
        ))
            ->withProvider(new ProviderTableProvider())
            ->populate();

        $this->tpl->setContent($table->getHtml());
    }

    /**
     * @param array{}|array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function wasInitializationStarted(array $task_info = []): bool
    {
        return (
            $task_info !== [] &&
            $task_info['state'] !== AbstractJobDefinition::JOB_STATE_INIT
        );
    }

    /**
     * @param array{}|array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function canInitializationStart(array $task_info = []): bool
    {
        return (
            $task_info === [] ||
            !in_array($task_info['state'], [
                AbstractJobDefinition::JOB_STATE_FINISHED,
                AbstractJobDefinition::JOB_STATE_RUNNING,
                AbstractJobDefinition::JOB_STATE_STARTED
            ], true));
    }

    /**
     * @param array{}|array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function isInitializationRunning(array $task_info = []): bool
    {
        return (
            $task_info !== [] &&
            $task_info['state'] !== AbstractJobDefinition::JOB_STATE_RUNNING
        );
    }

    /**
     * @param array{}|array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function hasInitializationFailed(array $task_info = []): bool
    {
        return $task_info !== [] && $task_info['state'] === AbstractJobDefinition::JOB_STATE_FAILED;
    }

    /**
     * @param array{}|array{"lock": bool, "state": string, "found_items": int, "processed_items": int, "progress": int, "started_ts": int, "finished_ts": null|int, "last_item": int} $task_info
     */
    private function hasInitializationFinished(array $task_info = []): bool
    {
        return $task_info !== [] && $task_info['state'] === AbstractJobDefinition::JOB_STATE_FINISHED;
    }
}
