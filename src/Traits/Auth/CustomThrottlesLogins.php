<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

trait CustomThrottlesLogins
{
	protected function hasTooManyLoginAttempts(Request $request): bool
	{
		$attempts = $this->getLoginAttemptCount($request);
		$lastAttempt = $this->getLoginAttemptedAt($request);

		if (!$attempts) {
			return false;
		}

		if ($attempts > 8) {
			return now()->subHour()->isBefore($lastAttempt);
		}

		if ($attempts > 5) {
			return now()->subMinutes(15)->isBefore($lastAttempt);
		}

		return now()->subMinutes(5)->isBefore($lastAttempt);
	}

	/**
	 * @throws ValidationException
	 */
	protected function sendLockoutResponse(Request $request): void
	{
		throw ValidationException::withMessages([
			$this->username() => ['Too many login attempts. Please try again soon.'],
		])->status(Response::HTTP_TOO_MANY_REQUESTS);
	}

	protected function clearLoginAttempts(Request $request): void
	{
		cache()->forget('login-attempt-count:' . $this->getLoginAttemptKey($request));
		cache()->forget('login-attempt-time:' . $this->getLoginAttemptKey($request));
	}

	protected function incrementLoginAttempts(Request $request): void
	{
		$attempts = $this->getLoginAttemptCount($request);
		cache()->put('login-attempt-count:' . $this->getLoginAttemptKey($request), $attempts + 1, now()->addDay());
		cache()->put('login-attempt-time:' . $this->getLoginAttemptKey($request), now(), now()->addDay());
	}

	private function getLoginAttemptCount(Request $request): int
	{
		return (int) cache()->get('login-attempt-count:' . $this->getLoginAttemptKey($request), 0);
	}

	private function getLoginAttemptedAt(Request $request): Carbon
	{
		return cache()->get('login-attempt-time:' . $this->getLoginAttemptKey($request)) ?: now()->subYear();
	}

	private function getLoginAttemptKey(Request $request): string
	{
		return Str::of($request->input($this->username()))->trim()->slug()->toString();
	}
}
