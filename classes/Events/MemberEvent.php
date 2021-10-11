<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\Events;

use QU\LERQ\Helper\EventDataAggregationHelper;
use QU\LERQ\Model\EventModel;
use QU\LERQ\Queue\Processor;

/**
 * Class MemberEvent
 * @package QU\LERQ\Events
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class MemberEvent extends AbstractEvent implements EventInterface
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
			->setEventName($a_event);
		if (substr($a_event, 0, 5) === 'init_') {
			if (isset($a_params['role_id'])) {
				$event->setRoleId($a_params['role_id']);
			}
		}
		if (isset($a_params['ref_id'])) {
			$event->setRefId($a_params['ref_id']);
		}

		$data = $processor->capture($event);
		$data['timestamp'] = time();
		$data['event'] = $this->mapInitEvent($a_event);

        $eventDataAggregator = EventDataAggregationHelper::singleton();
        $lp_data = $eventDataAggregator->getLpStatusInfoByUsrAndObjId($a_params['usr_id'], $a_params['obj_id']);
        $data['progress'] = $eventDataAggregator->getLpStatusRepresentation(isset($lp_data['status']) ? $lp_data['status'] : 0);
        if (is_array($lp_data)) {
            $data['progress_changed'] = $lp_data['status_changed'];
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