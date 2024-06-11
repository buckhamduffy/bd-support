<?php

namespace BuckhamDuffy\BdSupport\Listeners\Queue;

use Illuminate\Support\Arr;
use Illuminate\Queue\Events\JobTimedOut;
use BuckhamDuffy\BdSupport\Jobs\JobTimeoutInterface;

class JobTimeoutListener
{
	public function handle(JobTimedOut $event): void
	{
		$jobData = $event->job->payload();
		$jobSerialized = Arr::get($jobData, 'data.command');
		$jobClassName = Arr::get($jobData, 'data.commandName') ?: Arr::get($jobData, 'displayName');

		if (!$jobClassName || !$jobSerialized || !class_exists($jobClassName)) {
			return;
		}

		if ($this->checkJobImplements($jobClassName)) {
			/** @var JobTimeoutInterface $jobClass */
			$jobClass = unserialize($jobSerialized);

			if (property_exists($jobClass, 'job')) {
				$jobClass->job = $event->job;
			}

			$jobClass->onTimeout();
		}
	}

	/**
	 * @param class-string $jobClassName
	 */
	public function checkJobImplements(string $jobClassName): bool
	{
		$implements = class_implements($jobClassName);

		return \in_array(JobTimeoutInterface::class, $implements);
	}
}
