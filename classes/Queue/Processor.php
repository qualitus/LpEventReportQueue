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

namespace QU\LERQ\Queue;

use Exception;
use ilDBInterface;
use ilLogger;
use QU\LERQ\API\DataCaptureRoutinesInterface;
use QU\LERQ\Model\EventModel;
use QU\LERQ\Queue\CaptureRoutines\Routines;
use QU\LERQ\API\Service\Registration;
use QU\LERQ\Model\ProviderModel;
use QU\LERQ\Model\RoutinesModel;

class Processor
{
    private ilLogger $logger;
    private Registration $registration;
    /** @var array<string, array{"base": Routines, "collectUserData"?: DataCaptureRoutinesInterface, "collectUDFData"?: DataCaptureRoutinesInterface, "collectMemberData"?: DataCaptureRoutinesInterface, "collectLpPeriod"?: DataCaptureRoutinesInterface, "collectObjectData"?: DataCaptureRoutinesInterface}> */
    private array $routines;

    public function __construct()
    {
        global $DIC;

        $this->logger = $DIC->logger()->root();

        $this->registration = new Registration();
        $this->routines = $this->getRoutines();
    }

    /**
     * @return array<string, array{}|array<string, mixed>>
     */
    public function capture(EventModel $event): array
    {
        $this->logger->debug('Capture started');

        $data = [];

        foreach ($this->routines as $routine => $provider) {
            $this->logger->debug('capturing ' . $routine);

            if (is_array($provider) && count($provider) > 0) {
                if (count($provider) > 1) {
                    $provider = array_reverse($provider);
                }

                try {
                    $collect_func = lcfirst($routine);
                    $collector = $provider[array_keys($provider)[0]];
                    $collection = $collector->$collect_func($event);
                    if (empty($collection)) {
                        $collector = $provider['base'];
                        $collection = $collector->$collect_func($event);
                    }
                    $data[strtolower(substr($routine, 7))] = $collection;

                } catch (Exception $e) {
                    $this->logger->alert($e->getMessage());
                    $data[strtolower(substr($routine, 7))] = [];
                }
            }

            $this->logger->debug('capturing ' . $routine . ' finished');
        }

        $this->logger->debug('Capture finished');

        return $data;
    }

    /**
     * @return array<string, array{"base": Routines, "collectUserData"?: DataCaptureRoutinesInterface, "collectUDFData"?: DataCaptureRoutinesInterface, "collectMemberData"?: DataCaptureRoutinesInterface, "collectLpPeriod"?: DataCaptureRoutinesInterface, "collectObjectData"?: DataCaptureRoutinesInterface}>
     */
    private function getRoutines(): array
    {
        $providers = $this->registration->load();
        $overrides = [];

        $baseRoutines = new Routines();
        $available = $baseRoutines->getAvailableOverrrides();

        if ($providers !== []) {
            /** @var ProviderModel $provider */
            foreach ($providers as $provider) {
                if ($provider->getHasOverrides()) {
                    $routines_path = $provider->getPath() . '/CaptureRoutines/Routines.php'; // @Todo is there a better way to find the file?
                    require_once $routines_path;
                    $class = $provider->getNamespace() . '\Routines';
                    $pRoutinesClass = new $class();

                    if ($pRoutinesClass instanceof DataCaptureRoutinesInterface) {
                        $overrides[$provider->getName()]['routines'] = $pRoutinesClass;
                        $overrides[$provider->getName()]['overrides'] = $provider->getActiveOverrides();
                    }
                }
            }
        }

        $routines = [];
        foreach ($available as $override) {
            // first get base routines
            $routines[$override] = [
                'base' => $baseRoutines
            ];

            $test_func = 'get' . ucfirst($override);
            // get override routines
            if ($overrides !== []) {
                // $n => name
                // $o => array( routines, overrides )
                foreach ($overrides as $n => $o) {
                    if ($n === 'base') {
                        // prevent to override the baseRoutines, because we need it as fallback
                        continue;
                    }

                    /** @var RoutinesModel $o['overrides'] */
                    if (method_exists($o['overrides'], $test_func) && $o['overrides']->$test_func() === true) {
                        $routines[$override][$n] = $o['routines'];
                    }
                }
            }
        }

        return $routines;
    }
}
