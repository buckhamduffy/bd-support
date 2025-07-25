<?php

use BuckhamDuffy\BdSupport\Facades\Debug;

if (!\function_exists('captureException')) {
	function captureException(Throwable $e): void
	{
		Debug::exception($e);
	}
}

if (!\function_exists('captureContext')) {
	function captureContext(string $key, array $value): void
	{
		Debug::context($key, $value);
	}
}

if (!\function_exists('captureBreadcrumb')) {
	function captureBreadcrumb(string $message, array $metadata = [], string $category = 'Debug'): void
	{
		Debug::breadcrumb($message, $metadata, $category);
	}
}

if (!\function_exists('captureTag')) {
	function captureTag(string $key, string $value): void
	{
		Debug::tag($key, $value);
	}
}

if (!\function_exists('fluent_array')) {
	function fluent_array(array $array): BuckhamDuffy\BdSupport\Services\FluentArr
	{
		return new BuckhamDuffy\BdSupport\Services\FluentArr($array);
	}
}
