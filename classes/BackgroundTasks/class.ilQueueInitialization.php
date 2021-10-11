<?php

use \ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use \ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use \ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use \ILIAS\BackgroundTasks\Bucket;
use \ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use \ILIAS\BackgroundTasks\Types\SingleType;

/**
 * Class ilQueueInitialization
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class ilQueueInitialization extends AbstractUserInteraction
{
	const OPTION_START = 'startInitialization';
	const OPTION_CANCEL = 'cancel';

	/**
	 * @return array|\ILIAS\BackgroundTasks\Types\Type[]
	 */
	public function getInputTypes(): array
	{
		return [];
	}

	/**
	 * @return SingleType|\ILIAS\BackgroundTasks\Types\Type
	 */
	public function getOutputType(): SingleType
	{
		return new SingleType(StringValue::class);
	}

	/**
	 * @inheritDoc
	 */
	public function getRemoveOption() {
		return new UserInteractionOption('close', self::OPTION_CANCEL);
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions(array $input)
	{
		return [
//			new UserInteractionOption('start', self::OPTION_START),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function interaction(array $input, Option $user_selected_option, Bucket $bucket)
	{
		global $DIC;

		$progress = $input[0]->getValue();
		$logger = $DIC->logger()->root();

		$logger->debug('User interaction queue initialization State: '. $bucket->getState());
		if ($user_selected_option->getValue() != self::OPTION_START) {
			$logger->info(
				'User interaction queue initialization canceled by user with id: ' . $DIC->user()->getId()
			);
			return $input;
		}

		$logger->info(
			'User interaction queue initialization started by user with id: ' . $DIC->user()->getId()
		);

		return $input;
	}

}