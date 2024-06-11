<?php

namespace BuckhamDuffy\BdSupport\Facades;

use Throwable;
use Spatie\Ray\Ray;
use Sentry\State\HubInterface;
use Illuminate\Support\Optional;
use Illuminate\Support\Facades\Facade;
use BuckhamDuffy\BdSupport\Services\Debug as DebugBase;

/**
 * @see DBGBase
 * @method static DebugBase    exception(?Throwable $e)
 * @method static DebugBase    breadcrumb(string $message, array $metadata = [], string $category = "Debug")
 * @method static Ray|Optional ray(mixed ...$params)
 * @method static DebugBase    tag(string $key, string $value)
 * @method static DebugBase    context(string $key, array $value)
 * @method static HubInterface sentry()
 */
class Debug extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return DebugBase::class;
	}
}
