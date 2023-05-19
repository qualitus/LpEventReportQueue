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

interface Facade
{
    /**
     * Register a provider plugin
     *
     * This SHOULD be called in the plugins afterActivation() function
     * This MUST be called if the provider has capture overrides
     *
     * @param string $name			Plugin Name
     * @param string $namespace		Plugin Namespace
     * @param string $path			Plugin root path (realpath)
     * @param bool $hasOverrides	Does the plugin has overrides? (default: false)
     * @return bool
     */
    public function registerProvider(string $name, string $namespace, string $path, bool $hasOverrides = false): bool;

    /**
     * Update a provider plugins registration
     *
     * Only $path and $hasOverrides are updatable.
     * If you need to change $name or $namespace, you MUST use the
     * unregisterProvider() and then again the registerProvider() function.
     *
     * @param string $name
     * @param string $namespace
     * @param string $path
     * @param bool $hasOverrides
     * @return bool
     */
    public function updateProvider(string $name, string $namespace, string $path, bool $hasOverrides = null): bool;

    /**
     * Unregister a provider plugin
     *
     * This SHOULD be called in the plugins beforeUninstall() function
     *
     * @param string $name
     * @param string $namespace
     * @return bool
     */
    public function unregisterProvider(string $name, string $namespace): bool;

    /**
     * Create a new Filter object
     *
     * All setter methods of the filter object are chainable.
     * An filter object without defined filters, let the collection
     * return everything, but with a limit of 500. To deactivate
     * the page limit, use "->setPageLimit(-1)".
     *
     * Available methods:
     * ->setCourseStart(string $course_start, int $before_after)
     *   Filter for course | session start time (UTC Timestamp)
     * ->setCourseEnd(string $course_end, int $before_after)
     *   Filter for course | session end time (UTC Timestamp)
     * ->setProgress(string $progress)
     *   Filter for learning progress type (this locks eventType filter to 'lp_event')
     * ->setPageStart(int $page_start)
     *   Set id to start with
     * ->setPageLength(int $page_length)
     *   Set number of maximal entries | Default: 500
     * ->setNegativePager(bool $negative_pager)
     *   Set to TRUE to get a previous page | Default: FALSE
     * ->setEventType(string $event_type)
     *   Filter for specific event type
     * ->setEventHappened(string $event_happened, int $before_after)
     *   Filter for when the event happened (UTC Timestamp)
     *   Will be ignored if "setEventHappenedStart" and "setEventHappenedEnd" are used.
     * ->setEventHappenedStart(string $event_happened_start)
     *   Filter for when the event happened | Event start time (UTC Timestamp)
     * ->setEventHappenedEnd(string $event_happened_end)
     *   Filter for when the event happened | Event end time (UTC Timestamp)
     * ->setAssignment(string $assignment)
     *   Filter for user assignment role (this locks eventType filter to 'member_event')
     * ->setEvent(string $event)
     *   Filter for Event
     * ->setProgressChanged(string $progress_changed, int $before_after)
     *   Filter for last progress change (UTC Timestamp)
     *
     * @return Filter\FilterObject
     */
    public function createFilterObject(): \QU\LERQ\API\Filter\FilterObject;

    /**
     * Get a filtered Collection
     *
     * The QueueCollection object is iterable. It can be
     * used in foreach loops like an normal array.
     *
     * Available methods:
     * ->getAllItems()
     *   Get all items from collection as array
     * ->getItemKeys()
     *   Get array keys for the current item (also works inside foreach)
     * ->getIterator(bool $getnew)
     *   Get the CollectionIterator object (a new instance if $getnew is true) | Default: false
     *
     * @param Filter\FilterObject $filter    Filter object from createFilterObject()
     * @param bool $no_convert               If True, the collection holds only arrays, otherwise
     *                                       it holds an array of objects (see \QU\LERQ\Model\QueueModel)
     * @return \QU\LERQ\Collections\QueueCollection
     */
    public function getCollection(\QU\LERQ\API\Filter\FilterObject $filter, bool $no_convert = false): \QU\LERQ\Collections\QueueCollection;
}
