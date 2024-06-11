<?php

namespace BuckhamDuffy\BdSupport\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BuckhamDuffy\BdSupport\BdSupport
 */
class BdSupport extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return \BuckhamDuffy\BdSupport\BdSupport::class;
	}
}
