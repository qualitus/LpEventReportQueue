<?php

namespace QU\LERQ\Events;

class FakeEvent extends AbstractEvent implements EventInterface
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
        $a_params['event'] = $a_event;
        $a_params['timestamp'] = time();

        return $this->save($a_params);
    }
}