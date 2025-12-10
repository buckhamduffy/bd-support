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
				'text'  => static::humanReadable($case),
				'value' => $case->getBackingValue(),
				'name'  => $case->getName()
			], $reflection->getCases());
		}

		return static::$options[$enum] = array_map(fn (ReflectionEnumUnitCase $case): array => [
			'text'  => static::humanReadable($case),
			'value' => $case->getName(),
			'name'  => $case->getName()
		], $reflection->getCases());
	}

	public static function humanReadable(ReflectionEnumBackedCase|ReflectionEnumUnitCase $reflection): string
	{
		$name = $reflection->getName();
		if ($reflection instanceof ReflectionEnumBackedCase) {
			$value = $reflection->getBackingValue();
		} else {
			$value = $name;
		}

		if (strtoupper($value) !== $value) {
			if (!str_contains($value, ' ')) {
				return Str::headline($value);
			}

			return $value;
		}

		if (strtoupper($name) !== $name) {
			return Str::headline($name);
		}

		return $value;
	}
}
