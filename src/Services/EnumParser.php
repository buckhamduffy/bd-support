<?php

namespace BuckhamDuffy\BdSupport\Services;

use ReflectionEnum;
use ReflectionException;
use Illuminate\Support\Str;
use ReflectionEnumUnitCase;
use ReflectionEnumBackedCase;

class EnumParser
{
	public static array $options = [];
	public static array $backed = [];
	public static array $keys = [];

	public static function isBacked(string $enum): bool
	{
		if (\array_key_exists($enum, self::$backed)) {
			return self::$backed[$enum];
		}

		$reflection = new ReflectionEnum($enum);

		return self::$backed[$enum] = $reflection->isBacked();
	}

	/**
	 * @return string[]
	 */
	public static function keys(string $enum): array
	{
		return array_column(static::getOptions($enum), 'name');
	}

	/**
	 * @return string[]
	 */
	public static function values(string $enum): array
	{
		return array_column(static::getOptions($enum), 'value');
	}

	/**
	 * @return array{text: string, name: string, value: int|string}[]
	 * @throws ReflectionException
	 */
	public static function getOptions(string $enum): array
	{
		if (\array_key_exists($enum, self::$options)) {
			return self::$options[$enum];
		}

		$reflection = new ReflectionEnum($enum);

		if ($reflection->isBacked()) {
			return static::$options[$enum] = array_map(fn (ReflectionEnumBackedCase $case): array => [
				'text'  => static::humanReadableKey($case->getName()),
				'value' => $case->getBackingValue(),
				'name'  => $case->getName()
			], $reflection->getCases());
		}

		return static::$options[$enum] = array_map(fn (ReflectionEnumUnitCase $case): array => [
			'text'  => static::humanReadableKey($case->getName()),
			'value' => $case->getName(),
			'name'  => $case->getName()
		], $reflection->getCases());
	}

	public static function humanReadableKey(string $key): string
	{
		return preg_replace('/([a-z])([A-Z])/', '$1 $2', Str::studly($key));
	}
}
