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

use ILIAS\DI\Container;

class ilLpEventReportQueuePlugin extends \ilCronHookPlugin
{
    private const CTYPE = 'Services';
    private const CNAME = 'Cron';
    private const SLOT_ID = 'crnhk';
    public const PLUGIN_ID = "lpeventreportqueue";
    public const PLUGIN_NAME = "LpEventReportQueue";
    public const PLUGIN_SETTINGS = "qu_crnhk_lerq";
    public const PLUGIN_NS = 'QU\LERQ';

    private static ?self $instance = null;
    /** @var array<string, array<string, array<string, bool>>> */
    private static array $activePluginsCheckCache = [];
    /** @var array<string, array<string, array<string, ilPlugin>>> */
    private static array $activePluginsCache = [];

    private ilSetting $settings;
    private Container $dic;

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;

        $this->dic = $DIC;

        parent::__construct($db, $component_repository, $id);

        $this->settings = new ilSetting(self::PLUGIN_SETTINGS);
    }

    public static function getInstance(): self
    {
        global $DIC;

        if (self::$instance instanceof self) {
            return self::$instance;
        }

        /** @var ilComponentRepository $component_repository */
        $component_repository = $DIC['component.repository'];
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC['component.factory'];

        $plugin_info = $component_repository->getComponentByTypeAndName(
            self::CTYPE,
            self::CNAME
        )->getPluginSlotById(self::SLOT_ID)->getPluginByName(self::PLUGIN_NAME);

        self::$instance = $component_factory->getPlugin($plugin_info->getId());

        return self::$instance;
    }

    protected function init(): void
    {
        $this->registerAPI();
    }

    private function registerAPI(): void
    {
        $this->registerAutoloader();

        if (!isset($this->dic['qu.lerq.api'])) {
            $api = new \QU\LERQ\API\API();
            $this->dic['qu.lerq.api'] = $api;
        }
    }

    private function registerAutoloader(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        if (!isset($this->dic['autoload.lc.lcautoloader'])) {
            $Autoloader = new LCAutoloader();
            $Autoloader->register();
            $Autoloader->addNamespace('ILIAS\Plugin', '/Customizing/global/plugins');
            $this->dic['autoload.lc.lcautoloader'] = static fn (\ILIAS\DI\Container $c): LCAutoloader => $Autoloader;
        }

        $this->dic['autoload.lc.lcautoloader']->addNamespace(self::PLUGIN_NS, realpath(__DIR__));
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getSettings(): ilSetting
    {
        return $this->settings;
    }

    public function getCronJobInstances(): array
    {
        return [];
    }

    public function getCronJobInstance(string $jobId): ilCronJob
    {
        throw new RuntimeException('This plugin does not provide cron jobs');
    }

    protected function afterActivation(): void
    {
        if ($this->settings->get('lerq_first_start', '1') === true) {
            $this->initSettings();
        }
    }

    protected function beforeUninstall(): bool
    {
        if ($this->dic->database()->sequenceExists('lerq_queue')) {
            $this->dic->database()->dropSequence('lerq_queue');
        }
        if ($this->dic->database()->tableExists('lerq_queue')) {
            $this->dic->database()->dropTable('lerq_queue');
        }
        if ($this->dic->database()->sequenceExists('lerq_provider_register')) {
            $this->dic->database()->dropSequence('lerq_provider_register');
        }
        if ($this->dic->database()->tableExists('lerq_provider_register')) {
            $this->dic->database()->dropTable('lerq_provider_register');
        }
        if ($this->dic->database()->sequenceExists('lerq_settings')) {
            $this->dic->database()->dropSequence('lerq_settings');
        }
        if ($this->dic->database()->tableExists('lerq_settings')) {
            $this->dic->database()->dropTable('lerq_settings');
        }
        $this->settings->delete('lerq_first_start');
        $this->dic->settings()->delete('lerq_first_start');
        $this->settings->delete('lerq_bgtask_init');
        $this->dic->settings()->delete('lerq_bgtask_init');

        return true;
    }

    public function isPluginInstalled(string $component, string $slot, string $plugin_class): bool
    {
        if (isset(self::$activePluginsCheckCache[$component][$slot][$plugin_class])) {
            return self::$activePluginsCheckCache[$component][$slot][$plugin_class];
        }

        /** @var ilComponentRepository $component_repository */
        $component_repository = $this->dic['component.repository'];

        $has_plugin = $component_repository->getComponentByTypeAndName(
            'Services',
            $component
        )->getPluginSlotById($slot)->hasPluginName($plugin_class);

        if ($has_plugin) {
            $plugin_info = $component_repository->getComponentByTypeAndName(
                'Services',
                $component
            )->getPluginSlotById($slot)->getPluginByName($plugin_class);
            $has_plugin = $plugin_info->isActive();
        }

        return (self::$activePluginsCheckCache[$component][$slot][$plugin_class] = $has_plugin);
    }

    public function getPlugin(string $component, string $slot, string $plugin_class): ilPlugin
    {
        if (isset(self::$activePluginsCache[$component][$slot][$plugin_class])) {
            return self::$activePluginsCache[$component][$slot][$plugin_class];
        }

        /** @var ilComponentRepository $component_repository */
        $component_repository = $this->dic['component.repository'];
        /** @var ilComponentFactory $component_factory */
        $component_factory = $this->dic['component.factory'];

        $plugin_info = $component_repository->getComponentByTypeAndName(
            'Services',
            $component
        )->getPluginSlotById($slot)->getPluginByName($plugin_class);

        $plugin = $component_factory->getPlugin($plugin_info->getId());

        return (self::$activePluginsCache[$component][$slot][$plugin_class] = $plugin);
    }

    /**
     * @param array<string, mixed> $a_parameter
     */
    public function handleEvent(string $a_component, string $a_event, array $a_parameter): void
    {
        if (!$this->isActive()) {
            return;
        }

        $pl_settings = new \QU\LERQ\Model\SettingsModel();
        if ('1' != $pl_settings->getItem('user_fields')->getValue()) {
            return;
        }

        switch ($a_component) {
            case 'Modules/Course':
                $this->debuglog($a_component, $a_event, $a_parameter);
                switch ($a_event) {
                    /*
                     * $a_event: addParticipant
                     * $a_params: ['obj_id', 'usr_id', 'role_id']
                     */
                    case 'addParticipant':
                        $handler = new \QU\LERQ\Events\MemberEvent();
                        $handler->handle_event($a_event, $a_parameter);
                        break;
                        /*
                         * $a_event: deleteParticipant
                         * $a_params: ['obj_id', 'usr_id']
                         */
                    case 'deleteParticipant':
                        $handler = new \QU\LERQ\Events\MemberEvent();
                        $handler->handle_event($a_event, $a_parameter);
                        break;
                }
                break;

            case 'Services/Object':
                $this->debuglog($a_component, $a_event, $a_parameter);
                switch ($a_event) {
                    /*
                     * $a_event: toTrash
                     * $a_params: ['obj_id', 'ref_id', 'old_parent_ref_id']
                     */
                    case 'toTrash':
                        $handler = new \QU\LERQ\Events\ObjectEvent();
                        $handler->handle_event($a_event, $a_parameter);
                        break;
                        /*
                         * $a_event: undelete
                         * $a_params: ['obj_id', 'ref_id']
                         */
                    case 'undelete':
                        $handler = new \QU\LERQ\Events\ObjectEvent();
                        $handler->handle_event($a_event, $a_parameter);
                        break;
                }
                break;

            case 'Services/Tracking':
                $this->debuglog($a_component, $a_event, $a_parameter);
                /*
                 * $a_event: updateStatus
                 * $a_params: ['obj_id', 'usr_id', 'status', 'percentage']
                 */
                switch ($a_event) {
                    case 'updateStatus':
                        $handler = new \QU\LERQ\Events\LearningProgressEvent();
                        $handler->handle_event($a_event, $a_parameter);
                        break;
                }
                break;
        }
    }

    private function initSettings(): void
    {
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

        $this->settings->set('lerq_first_start', '0');

        $task_info = [
            'lock' => false,
            'state' => 'not started',
            'found_items' => 0,
            'processed_items' => 0,
            'progress' => 0,
            'started_ts' => time(),
            'finished_ts' => null,
            'last_item' => 0,
        ];
        $this->dic->settings()->set('lerq_bgtask_init', json_encode($task_info, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $a_params
     */
    private function debuglog(string $a_component, string $a_event, array $a_params): void
    {
        $dumper = new \QU\LERQ\Helper\TVarDumper();
        $this->dic->logger()->root()->debug(implode(' -> ', [
            print_r($a_component, true),
            print_r($a_event, true),
            $dumper::dump($a_params, 2),
        ]));
    }
}
