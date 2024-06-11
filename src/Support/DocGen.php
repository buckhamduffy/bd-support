<?php

namespace BuckhamDuffy\BdSupport\Support;

use JsonException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use Illuminate\Support\Facades\Facade;
use Spatie\StructureDiscoverer\Discover;

class DocGen
{
	public function run(string $path): void
	{
		$classes = Discover::in($path)
			->classes()
			->extending(Facade::class)
			->get();

		foreach ($classes as $class) {
			\Laravel\Prompts\info("Generating DocBlock for {$class}");
			$this->generate($class);
		}
	}

	/**
	 * @param class-string<Facade> $facade
	 */
	private function generate(string $facade): void
	{
		$facadeReflection = new ReflectionClass($facade);
		$method = $facadeReflection->getMethod('getFacadeAccessor');
		$method->setAccessible(true);
		$facadeRoot = $method->invoke(null);

		if (!class_exists($facadeRoot)) {
			return;
		}

		$docBlock = $this->generateDocBlockLines($facadeRoot);

		$this->apply($facade, $docBlock);
	}

	private function isSkippableMethod(ReflectionMethod $method): bool
	{
		if ($method->isConstructor()) {
			return true;
		}

		// Magic methods
		return str_starts_with($method->getName(), '__');
	}

	/**
	 * @return string[]
	 * @throws JsonException
	 * @throws ReflectionException
	 */
	private function generateDocBlockLines(string $class): array
	{
		$lines = [
			'/**',
			' * @see \\' . $class,
		];

		$reflection = new ReflectionClass($class);
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $method) {
			if ($this->isSkippableMethod($method)) {
				continue;
			}

			$paramStrings = $this->getParamStrings($method, $class);
			$returnString = $this->getReturnString($method, $class);

			$lines[] = sprintf(' * @method static %s %s(%s)', $returnString, $method->getName(), $paramStrings);
		}

		$lines[] = '*/';

		return $lines;
	}

	/**
	 * Get the PHPDoc string for the parameters of a method.
	 *
	 * @param  ReflectionMethod $method The method to get the PHPDoc string for.
	 * @return string           The PHPDoc string for the parameters of the method.
	 */
	private function getParamStrings(ReflectionMethod $method, string $class): string
	{
		$params = $method->getParameters();
		$paramStrings = [];

		foreach ($params as $param) {
			$paramString = '';
			if (($paramType = $param->getType()) !== null) {
				$paramString .= $this->getTypeName($paramType, $class) . ' ';
			}

			if ($param->isVariadic()) {
				$paramString .= '...';
			}

			$paramString .= '$' . $param->getName();

			if ($param->isOptional() && $param->isDefaultValueAvailable()) {
				$defaultValue = $param->getDefaultValue();
				$paramString .= ' = ' . json_encode($defaultValue, \JSON_THROW_ON_ERROR);
			}

			$paramStrings[] = $paramString;
		}

		return implode(', ', $paramStrings);
	}

	/**
	 * Get the name of a type, including union types.
	 *
	 * @param  ReflectionNamedType|ReflectionUnionType $type The type to get the name of.
	 * @return string                                  The name of the type, including union types.
	 */
	private function getTypeName(ReflectionNamedType|ReflectionUnionType $type, string $class, bool $isReturnType = false): string
	{
		if ($type instanceof ReflectionUnionType) {
			$typeNames = array_map(fn ($t): string => $this->getTypeName($t, $class, $isReturnType), $type->getTypes());

			return implode('|', $typeNames);
		}

		$typeName = $type->getName();
		if (!$type->isBuiltin()) {
			if (\in_array($typeName, ['self', 'static'], true)) {
				return '\\' . $class;
			}

			$typeName = '\\' . $typeName;
		}

		if ($typeName !== 'mixed' && $type->allowsNull() && !str_starts_with($typeName, '?')) {
			return $isReturnType ? 'null|' . $typeName : '?' . $typeName;
		}

		return $typeName;
	}

	/**
	 * Get the PHPDoc string for the return type of method.
	 *
	 * @param  ReflectionMethod $method The method to get the PHPDoc string for.
	 * @return string           The PHPDoc string for the return type of the method.
	 */
	private function getReturnString(ReflectionMethod $method, string $class): string
	{
		$returnString = '';

		if (($returnType = $method->getReturnType()) !== null) {
			$returnString .= $this->getTypeName($returnType, $class, true);
		} else {
			$returnString .= 'void';
		}

		return $returnString;
	}

	/**
	 * @param  string[]            $docBlock
	 * @throws ReflectionException
	 */
	private function apply(string $facade, array $docBlock): bool
	{
		$docBlock = implode(\PHP_EOL, $docBlock);

		$reflector = new ReflectionClass($facade);
		$filename = $reflector->getFileName();
		$existingDocBlock = $reflector->getDocComment();

		$contents = file_get_contents($filename);

		if ($existingDocBlock) {
			$newContents = str_replace($existingDocBlock, $docBlock, $contents);
		} else {
			$class = $reflector->getShortName();
			$newContents = preg_replace('/((final\s+)?class\s+' . $class . '\s+)/', $docBlock . \PHP_EOL . '$1', $contents);
		}

		return file_put_contents($filename, $newContents) !== false;
	}
}
