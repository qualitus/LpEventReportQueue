<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\API;

use QU\LERQ\API\Filter\FilterObject;
use QU\LERQ\API\Service\Collector;
use QU\LERQ\API\Service\Registration;

/**
 * Class API
 * @package QU\LERQ\API
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class API implements Facade
{
	/**
	 * @inheritDoc
	 */
	public function registerProvider(string $name, string $namespace, string $path, bool $hasOverrides = false): bool
	{
		$registration = new Registration();
		return $registration->create($name, $namespace, $path, $hasOverrides);
	}

	/**
	 * @inheritDoc
	 */
	public function updateProvider(string $name, string $namespace, string $path, bool $hasOverrides = null): bool
	{
		$registration = new Registration();
		return $registration->update($name, $namespace, $path, $hasOverrides);
	}

	/**
	 * @inheritDoc
	 */
	public function unregisterProvider(string $name, string $namespace): bool
	{
		$registration = new Registration();
		return $registration->remove($name, $namespace);
	}

	/**
	 * @inheritDoc
	 */
	public function createFilterObject(): \QU\LERQ\API\Filter\FilterObject
	{
		return new FilterObject();
	}

	/**
	 * @inheritDoc
	 */
	public function getCollection(\QU\LERQ\API\Filter\FilterObject $filter, bool $no_convert = false): \QU\LERQ\Collections\QueueCollection
	{
		$collector = new Collector($filter);
		return $collector->collect($no_convert);
	}

	/**
	 * @return string
	 */
	public function getCollectionScheme()
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
		]);
	}
}