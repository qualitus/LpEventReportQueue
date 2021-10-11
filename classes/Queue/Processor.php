<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Queue;

use QU\LERQ\API\DataCaptureRoutinesInterface;
use QU\LERQ\Model\EventModel;
use QU\LERQ\Queue\CaptureRoutines\Routines;
use QU\LERQ\API\Service\Registration;
use QU\LERQ\Model\ProviderModel;
use QU\LERQ\Model\RoutinesModel;

/**
 * Class Processor
 * @package QU\LERQ\Queue
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class Processor
{
	/** @var \ilDBInterface  */
	private $database;

	/** @var \ilLogger  */
	private $logger;

	/** @var Registration  */
	private $registration;

	/** @var array  */
	private $routines;

	/**
	 * Processor constructor.
	 */
	public function __construct()
	{
		global $DIC;

		$this->database = $DIC->database();
		$this->logger = $DIC->logger()->root();

		$this->registration = new Registration();
		$this->routines = $this->getRoutines();
	}

	/**
	 * @param EventModel $event
	 * @return array
	 */
	public function capture(EventModel $event)
	{
		$this->logger->debug('Capture started');

		$data = [];

		/**
		 * @var string $routine
		 * @var array $provider
		 */
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

				} catch (\Exception $e) {
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
	 * @return array
	 */
	private function getRoutines()
	{
		$providers = $this->registration->load();
		$overrides = [];

		$baseRoutines = new Routines();
		$available = $baseRoutines->getAvailableOverrrides();

		if (!empty($providers)) {
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

			$test_func = 'get'.ucfirst($override);
			// get override routines
			if (!empty($overrides)) {
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