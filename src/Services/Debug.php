<?php

namespace BuckhamDuffy\BdSupport\Services;

use Throwable;
use Spatie\Ray\Ray;
use Sentry\Breadcrumb;
use Sentry\State\Scope;
use Sentry\State\HubInterface;
use BuckhamDuffy\BdSupport\Support\Optional;

class Debug
{
	public function exception(?Throwable $e): self
	{
		$this->sentry()->captureException($e);

		$this->ray()->exception($e);

		return $this;
	}

	public function breadcrumb(string $message, array $metadata = [], string $category = 'Debug'): self
	{
		$this->sentry()->addBreadcrumb(new Breadcrumb(
			Breadcrumb::LEVEL_DEBUG,
			Breadcrumb::TYPE_DEFAULT,
			$category,
			$message,
			$metadata,
		));

		$this->ray([
			'message'  => $message,
			'metadata' => $metadata,
			'category' => $category,
		])->label('Breadcrumb');

		return $this;
	}

	/**
	 * @return Optional<Ray>|Ray
	 */
	public function ray(mixed ...$params): Ray|Optional
	{
		if (!app()->environment('local', 'testing')) {
			return new Optional(null);
		}

		if (class_exists(Ray::class)) {
			$ray = app(Ray::class);

			if ($ray->enabled()) {
				return app(Ray::class)->send(...$params);
			}
		}

		return new Optional(null);
	}

	public function tag(string $key, string $value): self
	{
		$this->sentry()->configureScope(function(Scope $scope) use ($key, $value): void {
			$scope->setTag($key, $value);
		});

		return $this;
	}

	public function context(string $key, array $value): self
	{
		$this->sentry()->configureScope(function(Scope $scope) use ($key, $value): void {
			$scope->setContext($key, $value);
		});

		return $this;
	}

	public function sentry(): HubInterface
	{
		return app('sentry');
	}
}
