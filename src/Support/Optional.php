<?php

namespace BuckhamDuffy\BdSupport\Support;

use ArrayAccess;
use Illuminate\Support\Arr;

/**
 * @template TValue
 * @mixin TValue
 * @implements ArrayAccess<key-of<TValue>, value-of<TValue>>
 */
class Optional implements ArrayAccess
{
	/**
	 * Create a new optional instance.
	 */
	public function __construct(
		/** The underlying object. */
		protected mixed $value
	)
	{
	}

	/**
	 * Dynamically access a property on the underlying object.
	 *
	 * @return null|value-of<TValue>
	 */
	public function __get(string $key): mixed
	{
		if (\is_object($this->value)) {
			return $this->value->{$key} ?? null;
		}

		if (\is_array($this->value)) {
			return $this->offsetGet($key);
		}

		return null;
	}

	/**
	 * Dynamically check a property exists on the underlying object.
	 *
	 * @param  key-of<TValue> $name
	 * @return bool
	 */
	public function __isset(mixed $name)
	{
		if (\is_object($this->value)) {
			return isset($this->value->{$name});
		}

		if (\is_array($this->value)) {
			return isset($this->value[$name]);
		}

		return false;
	}

	/**
	 * Determine if an item exists at an offset.
	 * @param key-of<TValue> $offset
	 */
	public function offsetExists(mixed $offset): bool
	{
		return Arr::accessible($this->value) && Arr::exists($this->value, $offset);
	}

	/**
	 * Get an item at a given offset.
	 * @param key-of<TValue> $offset
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return Arr::get($this->value, $offset);
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param TValue $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (Arr::accessible($this->value)) {
			$this->value[$offset] = $value;
		}
	}

	/**
	 * Unset the item at a given offset.
	 * @param key-of<TValue> $offset
	 */
	public function offsetUnset(mixed $offset): void
	{
		if (Arr::accessible($this->value)) {
			unset($this->value[$offset]);
		}
	}

	/**
	 * Dynamically pass a method to the underlying object.
	 */
	public function __call(string $method, array $parameters): mixed
	{
		if (\is_object($this->value)) {
			return $this->value->{$method}(...$parameters);
		}

		return $this;
	}
}
