<?php

namespace BuckhamDuffy\BdSupport\Jobs;

interface JobTimeoutInterface
{
	public function onTimeout(): void;
}
