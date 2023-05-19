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

namespace QU\LERQ\BackgroundTasks;

class AbstractJobDefinition
{
    // task was not started yet
    public const JOB_STATE_INIT = 'not started';
    // task was started but is not running yet
    public const JOB_STATE_STARTED = 'started';
    // task is currently running
    public const JOB_STATE_RUNNING = 'running';
    // task was stopped manually or by timeout
    public const JOB_STATE_STOPPED = 'stopped';
    // task has finished
    public const JOB_STATE_FINISHED = 'finished';
    // task has stopped because something failed
    public const JOB_STATE_FAILED = 'failed';

    // job has not started yet
    public const JOB_RETURN_INIT = 100;
    // job has run successful
    public const JOB_RETURN_SUCCESS = 200;
    // job is already running
    public const JOB_RETURN_ALREADY_RUNNING = 201;
    // job is locked
    public const JOB_RETURN_LOCKED = 202;
    // job has stopped i.e. by timeout
    public const JOB_RETURN_STOPPED = 203;
    // job has failed
    public const JOB_RETURN_FAILED = 400;
}
