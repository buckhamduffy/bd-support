<?php

namespace BuckhamDuffy\BdSupport\Resolvers;

use stdClass;
use DateTimeInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use BuckhamDuffy\BdSupport\Traits\TransformResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Converts JsonResource instances to arrays, handling nested resources and collections.
 * Useful for Inertia where you don't want it wrapped in ['data' => ...].
 */
class JsonResourceResolver
{
	use TransformResponse;

	public static function handle(JsonResource|ResourceCollection $resource, bool $topLevel = true): array
	{
		return app(JsonResourceResolver::class)->resolve($resource, $topLevel);
	}

	public function format(mixed $item): mixed
	{
		if (\is_array($item) || $item instanceof stdClass) {
			return array_map($this->format(...), (array) $item);
		}

		if (!\is_object($item)) {
			return $item;
		}

		if ($item instanceof DateTimeInterface) {
			return $item->format('c');
		}

		if ($item instanceof JsonResource) {
			return $this->resolve($item, false);
		}

		if (method_exists($item, 'toArray')) {
			return $this->format($item->toArray());
		}

		return array_map($this->format(...), (array) $item);
	}

	public function resolve(JsonResource $resource, bool $topLevel = true): array
	{
		if ($resource instanceof ResourceCollection) {
			return $this->resolveCollection($resource, $topLevel);
		}

		return array_map($this->format(...), $resource->resolve());
	}

	public function resolveCollection(ResourceCollection $collection, bool $topLevel = true): array
	{
		if (!$topLevel) {
			return $this->format($collection->resolve());
		}

		return array_merge_recursive(
			$this->transformPaginatedResponse(
				$collection->resource,
				$this->format($collection->resolve()),
			),
			$collection->with(request()),
			$collection->additional,
		);
	}
}
