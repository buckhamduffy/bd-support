<?php

return [
	'enable_queue_debugging' => env('BD_SUPPORT_ENABLE_QUEUE_DEBUGGING', false),

	'health' => [
		'env'     => env('APP_ENV', 'production'),
		'commit'  => env('COMMIT_SHA'),
		'branch'  => env('GIT_BRANCH', env('BRANCH_NAME')),
		'release' => env('SENTRY_RELEASE', env('COMMIT_SHA')),
	],
];
