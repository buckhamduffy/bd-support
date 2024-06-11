<?php

namespace BuckhamDuffy\BdSupport\Listeners\Queue;

use Illuminate\Support\Arr;
use Illuminate\Queue\Events\JobExceptionOccurred;
use BuckhamDuffy\BdSupport\Jobs\JobFailedInterface;

class JobExceptionListener
{
	public function handle(JobExceptionOccurred $event): void
	{
		$jobData = $event->job->payload();
		$jobSerialized = Arr::get($jobData, 'data.command');
		$jobClassName = Arr::get($jobData, 'data.commandName') ?: Arr::get($jobData, 'displayName');

		if (!$jobClassName || !$jobSerialized || !class_exists($jobClassName)) {
			return;
		}

		if ($this->checkJobImplements($jobClassName)) {
			/** @var JobFailedInterface $jobClass */
			$jobClass = unserialize($jobSerialized);

			if (property_exists($jobClass, 'job')) {
				$jobClass->job = $event->job;
			}

			$jobClass->failed($event->exception, $event->job->hasFailed());
		}
	}

	/**
	 * @param class-string $jobClassName
	 */
	public function checkJobImplements(string $jobClassName): bool
	{
		$implements = class_implements($jobClassName);

		return \in_array(JobFailedInterface::class, $implements);
	}
}
