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

namespace QU\LERQ\API;

use QU\LERQ\API\Filter\FilterObject;
use QU\LERQ\API\Service\Collector;
use QU\LERQ\API\Service\Registration;

class API implements Facade
{
    public function registerProvider(string $name, string $namespace, string $path, bool $hasOverrides = false): bool
    {
        return (new Registration())->create($name, $namespace, $path, $hasOverrides);
    }

    public function updateProvider(string $name, string $namespace, string $path, bool $hasOverrides = null): bool
    {
        return (new Registration())->update($name, $namespace, $path, $hasOverrides);
    }

    public function unregisterProvider(string $name, string $namespace): bool
    {
        return (new Registration())->remove($name, $namespace);
    }

    public function createFilterObject(): \QU\LERQ\API\Filter\FilterObject
    {
        return new FilterObject();
    }

    public function getCollection(
        \QU\LERQ\API\Filter\FilterObject $filter,
        bool $no_convert = false
    ): \QU\LERQ\Collections\QueueCollection {
        return (new Collector($filter))->collect($no_convert);
    }

    public function getCollectionScheme(): string
    {
        /**
         * integer   => integer
         * list      => array
         * object    => (object) Entry point for sub-object
         * string    => string
         * timestamp => (string) Timestamp ISO 8601
         */
        return json_encode([
            'id' => 'integer',
            'timestamp' => 'timestamp',
            'event' => 'string',
            'event_type' => 'string',
            'progress' => 'string',
            'progress_changed' => 'timestamp',
            'assignment' => 'string',
            'course_start' => 'timestamp',
            'course_end' => 'timestamp',
            'user_data' => 'object',
            'user_data.usr_id' => 'integer',
            'user_data.username' => 'string',
            'user_data.firstname' => 'string',
            'user_data.lastname' => 'string',
            'user_data.title' => 'string',
            'user_data.gender' => 'string',
            'user_data.email' => 'string',
            'user_data.institution' => 'string',
            'user_data.street' => 'string',
            'user_data.city' => 'string',
            'user_data.country' => 'string',
            'user_data.phone_office' => 'string',
            'user_data.hobby' => 'string',
            'user_data.department' => 'string',
            'user_data.phone_home' => 'string',
            'user_data.phone_mobile' => 'string',
            'user_data.fax' => 'string',
            'user_data.referral_comment' => 'string',
            'user_data.matriculation' => 'string',
            'user_data.active' => 'integer',
            'user_data.approval_date' => 'timestamp',
            'user_data.agree_date' => 'timestamp',
            'user_data.auth_mode' => 'string',
            'user_data.ext_account' => 'string',
            'user_data.birthday' => 'timestamp',
            'user_data.import_id' => 'string',
            'user_data.udf_data' => 'list',
            'obj_data' => 'object',
            'obj_data.obj_data.id' => 'integer',
            'obj_data.title' => 'string',
            'obj_data.ref_id' => 'integer',
            'obj_data.link' => 'string',
            'obj_data.type' => 'string',
            'obj_data.type_hr' => 'string',
            'obj_data.course_title' => 'string',
            'obj_data.course_id' => 'integer',
            'obj_data.course_ref_id' => 'integer',
            'mem_data' => 'object',
            'mem_data.role' => 'string',
            'mem_data.course_title' => 'string',
            'mem_data.course_id' => 'integer',
            'mem_data.course_ref_id' => 'integer',
        ], JSON_THROW_ON_ERROR);
    }
}
