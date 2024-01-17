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

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Types\SingleType;

class ilQueueInitialization extends AbstractUserInteraction
{
    public const OPTION_START = 'startInitialization';
    public const OPTION_CANCEL = 'cancel';

    public function getInputTypes(): array
    {
        return [
            new SingleType(IntegerValue::class),
        ];
    }

    public function getOutputType(): SingleType
    {
        return new SingleType(StringValue::class);
    }

    public function getRemoveOption(): Option
    {
        return new UserInteractionOption('close', self::OPTION_CANCEL);
    }

    public function getOptions(array $input): array
    {
        return [];
    }

    public function interaction(
        array $input,
        Option $user_selected_option,
        Bucket $bucket
    ): \ILIAS\BackgroundTasks\Value {
        global $DIC;

        $progress = $input[0]->getValue();
        $logger = $DIC->logger()->root();

        $logger->debug('User interaction queue initialization State: ' . $bucket->getState());
        if ($user_selected_option->getValue() !== self::OPTION_START) {
            $logger->info(
                'User interaction queue initialization canceled by user with id: ' . $DIC->user()->getId()
            );
        } else {
            $logger->info(
                'User interaction queue initialization finished by user with id: ' . $DIC->user()->getId()
            );
        }

        return $input[0];
    }
}
