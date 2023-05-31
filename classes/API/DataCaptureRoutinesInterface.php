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

use QU\LERQ\Model\EventModel;

interface DataCaptureRoutinesInterface
{
    /**
     * Array with TRUE / FALSE values to define which functions should be overwritten
     *   Overrides will work only if this function return an array with the functions' key and value TRUE
     *
     * @example
     * return [
     *   'collectUserData'   => false,
     *   'collectUDFData'    => false,
     *   'collectMemberData' => false,
     *   'collectLpPeriod'   => true,
     *   'collectObjectData' => false,
     * ];
     *
     * @return array<string, bool>
     */
    public function getOverrides(): array;

    /**
     * Array with collectable user data
     *
     * expected keys:
     *   user_id
     *   username
     *   firstname
     *   lastname
     *   title
     *   gender
     *   email
     *   institution
     *   street
     *   city
     *   country
     *   phone_office
     *   hobby
     *   phone_home
     *   phone_mobile
     *   phone_fax
     *   referral_comment
     *   matriculation
     *   active
     *   approval_date
     *   agree_date
     *   auth_mode
     *   ext_account
     *   birthday
     *   import_id
     *
     * @return array<string, mixed>
     */
    public function collectUserData(EventModel $event): array;

    /**
     * Array with udf data
     *
     * recommendation:
     *   To get the same output, everytime the function is called,
     *   you should return all field ids with null values, if the
     *   user has no data for this field.
     *
     * @return array<string, null|string>
     */
    public function collectUDFData(EventModel $event): array;

    /**
     * Array with member data
     *
     * expected keys:
     *   role,
     *   course_title
     *   course_id
     *   course_ref_id
     *
     * @return array<string ,mixed>
     */
    public function collectMemberData(EventModel $event): array;

    /**
     * Array of learning progress period
     *   e.g. course start and course end
     *
     * expected keys:
     *   course_start
     *   course_end
     *
     * @return array<string, mixed>
     */
    public function collectLpPeriod(EventModel $event): array;

    /**
     * Array of object data
     *
     * expected keys:
     *   id
     *   title
     *   ref_id
     *   link
     *   type
     *   course_title
     *   course_id
     *   course_ref_id
     *
     * @return array<string, mixed>
     */
    public function collectObjectData(EventModel $event): array;
}
