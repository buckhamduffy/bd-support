<?php

namespace BuckhamDuffy\BdSupport\Services;

use Throwable;
use Prewk\SerializedToAst;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use BuckhamDuffy\BdSupport\Facades\Debug;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Database\ModelIdentifier;

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
		$parser = new SerializedToAst();
		/** @var AstObj $ast */
		$ast = $parser->parse($this->serializedCommand)->toArray();

		if ($name = Arr::get($ast, 'name')) {
			Debug::tag('job.class', $name);
		}

		foreach (Arr::get($ast, 'public_properties', []) as $property => $value) {
			$this->processProperty($property, $value ?: []);
		}

		foreach (Arr::get($ast, 'protected_properties', []) as $property => $value) {
			$this->processProperty($property, $value ?: []);
		}

		foreach (Arr::get($ast, 'private_properties', []) as $property => $value) {
			$this->processProperty($property, $value ?: []);
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

	private function processProperty(string $name, array $ast): void
	{
		if (\in_array($name, $this->ignoredProperties)) {
			return;
		}

		$type = Arr::get($ast, 'type');
		$value = Arr::get($ast, 'value');

		if (!$type) {
			return;
		}

		if (\in_array($type, ['int', 'float', 'string', 'bool'])) {
			$this->context[$name] = $value;

			return;
		}

		if ($type == 'array') {
			$this->context[$name] = 'array[filtered]';

			return;
		}

		if ($type === 'object') {
			$this->processObject($ast);

			return;
		}
	}

	private function processObject(array $ast): void
	{
		$className = Arr::get($ast, 'name');

		if (!$className) {
			return;
		}

		if (!class_exists($className)) {
			return;
		}

		if ($className === ModelIdentifier::class) {
			$this->tags[] = [
				'key'   => class_basename(Arr::get($ast, 'public_properties.class.value')),
				'value' => Arr::get($ast, 'public_properties.id.value'),
			];

			return;
		}

		$parents = class_parents($className);

		if (!\in_array(Model::class, $parents)) {
			return;
		}

		$this->tags[] = [
			'key'   => class_basename($className),
			'value' => Arr::get($ast, 'protected_properties.attributes.items.id.value'),
		];
	}
}
