<?php

namespace BuckhamDuffy\BdSupport\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * @template TArray
 * @phpstan-consistent-constructor
 */
class FluentArr
{
	/**
	 * @param array<string, TArray> $array
	 */
	public function __construct(private array $array)
	{
	}

	/**
	 * @param array<array-key, array<array-key, TArray>> $arrays
	 */
	public static function make(array|string ...$arrays): static
	{
		return new static(array_merge([], ...$arrays));
	}

	/**
	 * @template TDefault
	 * @param  TDefault        $default
	 * @return TArray|TDefault
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		return Arr::get($this->array, $key, $default);
	}

	/**
	 * @param null|TArray $value
	 */
	public function set(string $key, mixed $value = null): self
	{
		Arr::set($this->array, $key, $value);

		return $this;
	}

	/**
	 * @param array<array-key, TArray> $items
	 */
	public function setAll(array $items): self
	{
		foreach ($items as $key => $value) {
			Arr::set($this->array, $key, $value);
		}

		return $this;
	}

	/**
	 * Returns the first of the given keys that is present and not empty.
	 * @param string[] $keys
	 * @template TDefault
	 * @param  TDefault        $default
	 * @return TArray|TDefault
	 */
	public function firstOf(array $keys, mixed $default = null): mixed
	{
		foreach ($keys as $key) {
			if ($value = $this->get($key)) {
				return $value;
			}
		}

		return $default;
	}

	/**
	 * @template TDefault
	 * @param  TDefault        $default
	 * @return TArray|TDefault
	 */
	public function input(string $key, mixed $default = null): mixed
	{
		return $this->get($key, $default);
	}

	public function filled(string $key): bool
	{
		return !$this->blank($key);
	}

	public function blank(string $key): bool
	{
		return blank($this->get($key));
	}

	/**
	 * @template TDefault
	 * @param  TDefault                         $default
	 * @return array<array-key, mixed>|TDefault
	 */
	public function json(string $key, mixed $default = null): mixed
	{
		$value = $this->get($key);

		if (!$value) {
			return $default;
		}

		if (!\is_string($value)) {
			return $default;
		}

		return str_starts_with($value, '{') ? json_decode($value, true, 512, \JSON_THROW_ON_ERROR) : $default;
	}

	public function bool(string $key, mixed $default = null): bool
	{
		return $this->boolean($key, $default);
	}

	public function boolean(string $key, mixed $default = null): bool
	{
		return filter_var($this->get($key, $default), \FILTER_VALIDATE_BOOLEAN);
	}

	public function integer(string $key, mixed $default = null): int
	{
		return (int) ($this->get($key, $default));
	}

	public function int(string $key, mixed $default = null): int
	{
		return $this->integer($key, $default);
	}

	public function float(string $key, mixed $default = null): float
	{
		return (float) ($this->get($key, $default));
	}

	public function string(string $key, mixed $default = null): string
	{
		return (string) ($this->get($key, $default));
	}

	public function date(string $key, null|string|DateTimeInterface $default = null, ?string $format = null): ?Carbon
	{
		$value = $this->get($key, $default);

		if (!$value) {
			if ($default) {
				return Carbon::parse($default);
			}

			return null;
		}

		if ($format) {
			return Carbon::createFromFormat($format, $value);
		}

		return Carbon::parse($value);
	}

	public function enum(string $key, string $enumClass)
	{
		if (!$this->filled($key)
			|| !enum_exists($enumClass)
			|| !method_exists($enumClass, 'tryFrom')) {
			return null;
		}

		return $enumClass::tryFrom($this->get($key));
	}

	public function asArray(string $key, mixed $default = null): array
	{
		$value = $this->get($key, $default);

		if (\is_array($value)) {
			return $value;
		}

		// if the value is a single string, json_decode returns the string,
		// so we need to parse it to array
		if (Str::isJson($value)) {
			return (array) json_decode((string) $value, true, 512, \JSON_THROW_ON_ERROR);
		}

		if (\is_string($this->get($key, $default))) {
			return explode(',', $this->get($key, $default));
		}

		return [];
	}

	public function all(): array
	{
		return $this->array;
	}

	/**
	 * add an array to the existing array
	 */
	public function merge(array ...$array): self
	{
		$this->array = array_merge($this->array, ...$array);

		return $this;
	}

	/**
	 * Flatten a multi-dimensional associative array with dots.
	 * Returns new FluentArr instance.
	 */
	public function dot(string $prepend = ''): static
	{
		return new static(Arr::dot($this->array, $prepend));
	}

	/**
	 * Convert a flatten "dot" notation array into an expanded array.
	 */
	public function undot(): static
	{
		return new static(Arr::undot($this->array));
	}

	/**
	 * Get all of the given array except for a specified array of keys.
	 */
	public function except(array $keys): array
	{
		return Arr::except($this->array, $keys);
	}

	/**
	 * Get a subset of the items from the given array.
	 */
	public function only(string ...$keys): array
	{
		return Arr::only($this->array, $keys);
	}

	public function pluck(string $value, ?string $key = null): array
	{
		return Arr::pluck($this->array, $value, $key);
	}

	public function has(string $key): bool
	{
		return Arr::has($this->array, $key);
	}

	public function first(mixed $default = null): mixed
	{
		return Arr::first($this->array, default: $default);
	}

	public function last(mixed $default = null): mixed
	{
		return Arr::last($this->array, default: $default);
	}

	/**
	 * Flatten a multi-dimensional array into a single level.
	 * Returns new FluentArr instance.
	 */
	public function flatten(): self
	{
		return new static(Arr::flatten($this->array));
	}

	public function forget(string ...$keys): self
	{
		foreach ($keys as $key) {
			Arr::forget($this->array, $key);
		}

		return $this;
	}

	public function collection(?string $key = null, mixed $default = null): Collection
	{
		if ($key === null) {
			return collect($this->array);
		}

		return collect($this->get($key, $default));
	}

	public function isEmptyString(string $key, mixed $default = null): bool
	{
		$value = $this->input($key, $default);

		return !\is_bool($value) && !\is_array($value) && blank($value);
	}
}
