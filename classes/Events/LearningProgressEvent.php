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

namespace QU\LERQ\Events;

use QU\LERQ\Helper\EventDataAggregationHelper;
use QU\LERQ\Model\EventModel;
use QU\LERQ\Queue\Processor;

class LearningProgressEvent extends AbstractEvent
{
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
        $data['progress'] = $eventDataAggregator->getLpStatusRepresentation((int) $a_params['status']);
        if (strpos($a_event, 'init_') === 0) {
            $td = $eventDataAggregator->getLpStatusChangedByUsrAndObjId(
                (int) $a_params['usr_id'],
                (int) $a_params['obj_id']
            );
            $data['timestamp'] = $td > 0 ? $td : $data['timestamp'];
        }
        $data['assignment'] = '-';
        if (($data['memberdata']['role'] ?? null) !== null) {
            $data['assignment'] = $eventDataAggregator->getRoleTitleByRoleId((int) $data['memberdata']['role']);
        } else {
            $ref_id = ($event->getRefId() > 0 ? $event->getRefId() : (
                ($data['memberdata']['course_ref_id'] ?? 0) > 0 ? (int) $data['memberdata']['course_ref_id'] : 0
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
