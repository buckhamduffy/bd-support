<?php

namespace BuckhamDuffy\BdSupport\Traits;

use ReflectionException;
use BuckhamDuffy\BdSupport\Services\EnumParser;

/**
 * @mixin \Enum
 */
trait EnumArray
{
	public static function isBacked(): bool
	{
		return EnumParser::isBacked(self::class);
	}

	public static function isUnit(): bool
	{
		return !self::isBacked();
	}

	/**
	 * @return string[]
	 */
	public static function values(): array
	{
		return EnumParser::values(self::class);
	}

	/**
	 * @return string[]
	 */
	public static function keys(): array
	{
		return EnumParser::keys(self::class);
	}

	/**
	 * @return array{text: string, name: string, value: int|string}[]
	 * @throws ReflectionException
	 */
	public static function asOptions(): array
	{
		return EnumParser::getOptions(self::class);
	}
}
