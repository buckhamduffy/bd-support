<?php

namespace BuckhamDuffy\BdSupport\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

trait TransformResponse
{
	/**
	 * @param array|Collection|LengthAwarePaginator $paginator
	 * @param null|array|Collection                 $mappedItems
	 */
	protected function transformPaginatedResponse(mixed $paginator, mixed $mappedItems = null, array $extra = []): array
	{
		if ($paginator instanceof LengthAwarePaginator) {
			$data = $paginator->toArray();

			return array_merge($extra, [
				'data' => filled($mappedItems) ? $mappedItems : Arr::get($data, 'data'),
				'meta' => Arr::except(
					$data,
					['data', 'first_page_url', 'last_page_url', 'next_page_url', 'prev_page_url']
				),
				'links' => [
					'first' => Arr::get($data, 'first_page_url'),
					'last'  => Arr::get($data, 'last_page_url'),
					'prev'  => Arr::get($data, 'prev_page_url'),
					'next'  => Arr::get($data, 'next_page_url'),
				]
			]);
		}

		return array_merge($extra, ['data' => filled($mappedItems) ? $mappedItems : $paginator]);
	}
}
