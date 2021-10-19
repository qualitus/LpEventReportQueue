<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */
namespace QU\LERQ\Events;

include_once './Services/Tracking/classes/class.ilLPStatus.php';

use QU\LERQ\Helper\EventDataAggregationHelper;
use QU\LERQ\Model\EventModel;
use QU\LERQ\Queue\Processor;

/**
 * Class LearningProgressEvent
 * @package QU\LERQ\Events
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class LearningProgressEvent extends AbstractEvent implements EventInterface
{
	/** @var \ilLog */
	protected $logger;

	/** @var \ilDB */
	protected $database;

	/** @var \ilIniFile */
	protected $configInstance;

	/**
	 * @param string $a_event
	 * @param array $a_params
	 * @return bool
	 */
	public function handle_event(string $a_event, array $a_params): bool
	{
		$processor = new Processor();
		$event = new EventModel();

		$event->setObjId($a_params['obj_id'])
			->setUsrId($a_params['usr_id'])
			->setLpStatus($a_params['status'])
			->setLpPercentage($a_params['percentage'])
			->setEventName($a_event);
		if (isset($a_params['ref_id'])) {
			$event->setRefId($a_params['ref_id']);
		}

		$data = $processor->capture($event);
		$data['timestamp'] = time();
		$data['progress_changed'] = time();
		$data['event'] = $this->mapInitEvent($a_event);

		$eventDataAggregator = EventDataAggregationHelper::singleton();
		$data['progress'] = $eventDataAggregator->getLpStatusRepresentation($a_params['status']);
        if (substr($a_event, 0, 5) === 'init_') {
            $td = $eventDataAggregator->getLpStatusChangedByUsrAndObjId($a_params['usr_id'], $a_params['obj_id']);
            $data['timestamp'] = ($td > 0 ? $td : $data['timestamp']);
        }
		$data['assignment'] = '-';
		if ($data['memberdata']['role'] !== NULL) {
			$data['assignment'] = $eventDataAggregator->getRoleTitleByRoleId($data['memberdata']['role']);
		} else {
			$ref_id = ($event->getRefId() > 0 ? $event->getRefId() : (
				$data['memberdata']['course_ref_id'] > 0 ? $data['memberdata']['course_ref_id'] : 0
			));
			if ($ref_id > 0) {
				$assignment = $eventDataAggregator->getParentContainerAssignmentRoleForObjectByRefIdAndUserId(
					$ref_id,
					$event->getUsrId()
				);
				if ($assignment != -1) {
					$data['assignment'] = $eventDataAggregator->getRoleTitleByRoleId($assignment);
				}
			}
		}

		return $this->save($data);
	}

}