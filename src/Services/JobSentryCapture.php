<?php

namespace BuckhamDuffy\BdSupport\Services;

use Throwable;
use BuckhamDuffy\BdSupport\Facades\Debug;
use Illuminate\Queue\Events\JobProcessing;
use Mochaka\SerializationParser\Data\ClassType;
use Mochaka\SerializationParser\Data\FloatType;
use Mochaka\SerializationParser\Data\BooleanType;
use Mochaka\SerializationParser\Data\IntegerType;
use Mochaka\SerializationParser\SerializationParser;
use Mochaka\SerializationParser\Interfaces\TypeInterface;

/**
 * @phpstan-type AstType array{type: string, value: mixed}
 * @phpstan-type AstArray array{name: string, items: array<array-key, AstType>}
 * @phpstan-type AstObj array{
 *     type: string,
 *     name: string,
 *     public_properties: array<string, AstType|AstArray>,
 *     protected_properties: array<string, AstType|AstArray>,
 *     private_properties: array<string, AstType|AstArray>,
 * }
 *
 */
class JobSentryCapture
{
	/** @var array<string, mixed> */
	private array $context = [];

	/** @var array<string, mixed> */
	private array $tags = [];

	/** @var array|string[] */
	private array $ignoredProperties = [
		'chainConnection',
		'chainQueue',
		'chainCatchCallbacks',
		'afterCommit',
		'chained',
		'job',
	];

	public function __construct(private readonly string $serializedCommand)
	{
	}

	public static function handle(JobProcessing $jobProcessing): void
	{
		if (!class_exists($jobProcessing->job->resolveName())) {
			return;
		}

		try {
			(new self($jobProcessing->job->resolveName()))->process();
		} catch (Throwable $throwable) {
			Debug::exception($throwable);
		}
	}

	public function process(): void
	{
		/** @var ClassType $parser */
		$parser = SerializationParser::parse($this->serializedCommand);

		if ($name = $parser->getName()) {
			Debug::tag('job.class', $name);
		}

		foreach ($parser->getProperties() as $property) {
			$this->processProperty($property);
		}

		if (\count($this->context)) {
			Debug::context('job', $this->context);
		}

		foreach ($this->tags as $tag) {
			if (\is_null($tag['value'])) {
				continue;
			}

			Debug::tag($tag['key'], $tag['value']);
		}
	}

	private function processProperty(TypeInterface $property): void
	{
		if (\in_array($property->getName(), $this->ignoredProperties)) {
			return;
		}

		if ($property instanceof IntegerType || $property instanceof FloatType || $property instanceof BooleanType) {
			$this->context[$property->getName()] = $property->getValue();

			return;
		}

		if ($property->isArray() && $property->getName()) {
			$this->context[$property->getName()] = 'array[filtered]';

			return;
		}

		if ($property instanceof ClassType) {
			$this->processObject($property);
		}
	}

	private function processObject(ClassType $property): void
	{
	}
}
