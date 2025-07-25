<?php

namespace BuckhamDuffy\BdSupport;

use Spatie\LaravelPackageTools\Package;
use Illuminate\Queue\Events\JobTimedOut;
use Illuminate\Queue\Events\JobProcessing;
use BuckhamDuffy\BdSupport\Services\FluentArr;
use Illuminate\Queue\Events\JobExceptionOccurred;
use BuckhamDuffy\BdSupport\Services\JobSentryCapture;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use BuckhamDuffy\BdSupport\Commands\TypescriptEnumCommand;
use BuckhamDuffy\BdSupport\Listeners\Queue\JobTimeoutListener;
use BuckhamDuffy\BdSupport\Listeners\Queue\JobExceptionListener;
use BuckhamDuffy\BdSupport\Commands\SynapseSendHealthcheckEmailCommand;

class BdSupportServiceProvider extends PackageServiceProvider
{
	public function configurePackage(Package $package): void
	{
		/*
		 * This class is a Package Service Provider
		 *
		 * More info: https://github.com/spatie/laravel-package-tools
		 */
		$package->name('bd-support')
			->hasConfigFile()
			->hasRoute('web')
			->hasCommand(TypescriptEnumCommand::class)
			->hasCommand(SynapseSendHealthcheckEmailCommand::class);
	}

	public function boot(): void
	{
		parent::boot();

		if (config('bd-support.enable_queue_debugging')) {
			$this->app['events']->listen(JobProcessing::class, JobSentryCapture::class);
			$this->app['events']->listen(JobExceptionOccurred::class, JobExceptionListener::class);
			$this->app['events']->listen(JobTimedOut::class, JobTimeoutListener::class);
		}

		$this->app->bind(FluentArr::class, fn ($app) => FluentArr::make($app['request']->all()));
	}
}
