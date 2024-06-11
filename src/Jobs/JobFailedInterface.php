<?php

namespace BuckhamDuffy\BdSupport\Jobs;

use Throwable;

interface JobFailedInterface
{
	public function failed(Throwable $e, bool $fullFailure = true): void;
}
