<?php

namespace BuckhamDuffy\BdSupport\Controllers;

use Illuminate\Http\JsonResponse;

class SynapseInfoController extends Controller
{
	public function __invoke(): JsonResponse
	{
		return new JsonResponse([
			'commit'  => config('bd-support.health.commit'),
			'branch'  => config('bd-support.health.branch'),
			'env'     => config('bd-support.health.env'),
			'release' => config('bd-support.health.release'),
		]);
	}
}
