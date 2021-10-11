<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Events;

use QU\LERQ\Helper\EventDataAggregationHelper;
use QU\LERQ\Model\EventModel;
use QU\LERQ\Queue\Processor;

/**
 * Class ObjectEvent
 * @package QU\LERQ\Events
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class ObjectEvent extends AbstractEvent implements EventInterface
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

		global $DIC;

		$event->setObjId($a_params['obj_id'])
            ->setUsrId($DIC->user()->getId())
			->setEventName($a_event);
		if (isset($a_params['ref_id'])) {
			$event->setRefId($a_params['ref_id']);
		}
		if (isset($a_params['obj_type'])) {
			$event->setObjType($a_params['obj_type']);
		} else if ($a_params['type']) {
			$event->setObjType($a_params['type']);
		}
		if (isset($a_params['old_parent_ref_id'])) {
			$event->setParentRefId($a_params['old_parent_ref_id']);
		} else if (isset($a_params['parent_ref_id'])) {
			$event->setParentRefId($a_params['parent_ref_id']);
		}
		// do we have a change to get the user id from the akteur?

		$data = $processor->capture($event);
		$data['timestamp'] = time();
		$data['event'] = $this->mapInitEvent($a_event);

		$eventDataAggregator = EventDataAggregationHelper::singleton();
        $data['progress'] = $eventDataAggregator->getLpStatusRepresentation();
        $data['progress_changed'] = '';
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
					$event->getUsrId(),
                    $a_event
				);
				if ($assignment != -1) {
					$data['assignment'] = $eventDataAggregator->getRoleTitleByRoleId($assignment);
				}
			}
		}

		return $this->save($data);
	}

}