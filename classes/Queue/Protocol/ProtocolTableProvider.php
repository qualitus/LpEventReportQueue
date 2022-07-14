<?php declare(strict_types=1);

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
 ********************************************************************
 */

namespace QU\LERQ\Queue\Protocol;

use ilDBConstants;
use InvalidArgumentException;
use QU\LERQ\UI\Table\Data\DatabaseProvider;

class ProtocolTableProvider extends DatabaseProvider
{
    protected function getSelectPart(array $params, array $filter) : string
    {
        $fields = [
            'lerqq.*',
        ];

        return implode(', ', $fields);
    }

    protected function getFromPart(array $params, array $filter) : string
    {
        $joins = [];

        return 'lerq_queue lerqq ' . implode(' ', $joins);
    }

    protected function getWherePart(array $params, array $filter) : string
    {
        $whereConjunctions = [];

        if (isset($filter['query']) && is_string($filter['query']) && $filter['query'] !== '') {
            $whereConjunctions[] = '(' . implode(' OR ', [
                $this->db->like('lerqq.user_data', ilDBConstants::T_TEXT, '%' . $filter['query'] . '%'),
                $this->db->like('lerqq.mem_data', ilDBConstants::T_TEXT, '%' . $filter['query'] . '%'),
                $this->db->like('lerqq.obj_data', ilDBConstants::T_TEXT, '%' . $filter['query'] . '%'),
            ]) . ')';
        }

        if (isset($filter['id']) && is_string($filter['id']) && is_numeric($filter['id'])) {
            $whereConjunctions[] = 'lerqq.id = ' . $this->db->quote($filter['id'], ilDBConstants::T_INTEGER);
        }

        if (isset($filter['subject_id']) && is_string($filter['subject_id']) && is_numeric($filter['subject_id'])) {
            $whereConjunctions[] = "JSON_EXTRACT(user_data, '$.usr_id') = " . $this->db->quote($filter['subject_id'], ilDBConstants::T_INTEGER);
        }

        if (isset($filter['object_id']) && is_string($filter['object_id']) && is_numeric($filter['object_id'])) {
            $whereConjunctions[] = "JSON_EXTRACT(obj_data, '$.id') = " . $this->db->quote($filter['object_id'], ilDBConstants::T_INTEGER);
        }

        if (isset($filter['object_ref_id']) && is_string($filter['object_ref_id']) && is_numeric($filter['object_ref_id'])) {
            $whereConjunctions[] = "JSON_EXTRACT(obj_data, '$.ref_id') = " . $this->db->quote($filter['object_ref_id'], ilDBConstants::T_INTEGER);
        }

        if (isset($filter['subject_login']) && is_string($filter['subject_login']) && $filter['subject_login'] !== '') {
            $whereConjunctions[] = '(' . implode(' OR ', [
                $this->db->like(
                    "JSON_EXTRACT(user_data, '$.username')",
                    ilDBConstants::T_TEXT,
                    '%' . $filter['subject_login'] . '%'
                ),
            ]) . ')';
        }

        if (isset($filter['object_title']) && is_string($filter['object_title']) && $filter['object_title'] !== '') {
            $whereConjunctions[] = '(' . implode(' OR ', [
                    $this->db->like(
                        "JSON_EXTRACT(obj_data, '$.title')",
                        ilDBConstants::T_TEXT,
                        '%' . $filter['object_title'] . '%'
                    ),
            ]) . ')';
        }

        if (isset($filter['timestamp']) && is_array($filter['timestamp'])) {
            $dateFilterParts = [];

            if (null !== $filter['timestamp']['from']) {
                $dateFilterParts[] = 'lerqq.timestamp >= ' . $this->db->quote(
                    $filter['timestamp']['from']->get(IL_CAL_UNIX),
                    ilDBConstants::T_INTEGER
                );
            }

            if (null !== $filter['timestamp']['to']) {
                $dateFilterParts[] = 'lerqq.timestamp <= ' . $this->db->quote(
                    $filter['timestamp']['to']->get(IL_CAL_UNIX),
                    ilDBConstants::T_INTEGER
                );
            }

            if (count($dateFilterParts) > 0) {
                $whereConjunctions[] = '(' . implode(' AND ', $dateFilterParts) . ')';
            }
        }

        return implode(' AND ', $whereConjunctions);
    }

    protected function getGroupByPart(array $params, array $filter) : string
    {
        return '';
    }

    protected function getHavingPart(array $params, array $filter) : string
    {
        return '';
    }

    protected function getOrderByPart(array $params, array $filter) : string
    {
        if (isset($params['order_field'])) {
            if (!is_string($params['order_field'])) {
                throw new InvalidArgumentException('Please provide a valid order field.');
            }

            $order_field = $params['order_field'];

            $sortableColumns = [
                'id',
                'timestamp',
                'event_type',
                'subject_id',
                'subject_title',
                'subject_email_addr',
                'object_id',
                'object_ref_id',
                'object_title',
                'progress',
            ];

            if (!in_array($order_field, $sortableColumns, true)) {
                $order_field = 'id';
            }

            if (!isset($params['order_direction'])) {
                $order_direction = 'ASC';
            } else {
                $order_direction = strtoupper($params['order_direction']);
            }
            
            if (!in_array(strtolower($order_direction), ['asc', 'desc'], true)) {
                throw new InvalidArgumentException('Please provide a valid order direction.');
            }

            if ('event_type' === $order_field) {
                return 'event ' . $order_direction . ', event_type ' . $order_direction;
            }
            if ('subject_id' === $order_field) {
                return "JSON_EXTRACT(user_data, '$.usr_id') " . $order_direction;
            }
            if ('subject_title' === $order_field) {
                return "JSON_EXTRACT(user_data, '$.username') " . $order_direction;
            }
            if ('subject_email_addr' === $order_field) {
                return "JSON_EXTRACT(user_data, '$.email') " . $order_direction;
            }
            if ('object_id' === $order_field) {
                return "JSON_EXTRACT(obj_data, '$.id') " . $order_direction;
            }
            if ('object_ref_id' === $order_field) {
                return "JSON_EXTRACT(obj_data, '$.ref_id') " . $order_direction;
            }
            if ('object_title' === $order_field) {
                return "JSON_EXTRACT(obj_data, '$.title') " . $order_direction;
            }
            if ('progress' === $order_field) {
                return 'progress ' . $order_direction;
            }

            return $order_field . ' ' . $order_direction;
        }

        return '';
    }
}
