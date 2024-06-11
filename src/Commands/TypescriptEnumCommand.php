<?php

namespace BuckhamDuffy\BdSupport\Commands;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\StructureDiscoverer\Discover;
use BuckhamDuffy\BdSupport\Services\EnumParser;

class TypescriptEnumCommand extends Command
{
	protected $signature = 'typescript:enums {--keep-existing} {--ext=js}';
	protected $description = 'Converts Enums into Typescript';
	private array $enums = [];

	public function handle(): int
	{
		/** @var string[] $enums */
		$enums = spin(fn () => Discover::in(app_path())->enums()->get(), 'Finding Enums...');

		if (!\count($enums)) {
			error('No Enums found');

			return Command::FAILURE;
		}

		if (!$this->option('keep-existing')) {
			File::cleanDirectory(resource_path('enums'));
		}

		foreach ($enums as $enum) {
			$this->processEnum($enum);
		}

		table(['Enum', 'Path'], $this->enums);

		return Command::SUCCESS;
	}

	private function processEnum(string $enum): void
	{
		$path = $this->enumToPath($enum);
		$fileName = class_basename($enum) . '.' . $this->option('ext');
		$file = $path . \DIRECTORY_SEPARATOR . $fileName;

		$this->enums[] = [
			'enum' => $enum,
			'path' => str_replace(base_path(), '', $file),
		];

		$lines = [];
		$cases = EnumParser::getOptions($enum);

		foreach ($cases as $case) {
			$lines[] = sprintf('export const %s = %s;', $case['name'], var_export($case['value'], true));
		}

		$lines[] = '';

		$lines[] = sprintf(
			'export const %s = %s;',
			Str::camel(class_basename($enum)),
			json_encode($cases, \JSON_PRETTY_PRINT)
		);

		File::put($file, implode(\PHP_EOL, $lines));
	}

	private function enumToPath(string $enum): string
	{
		$path = resource_path('enums');
		$parts = explode('\\', $enum);

		$count = \count($parts);
		while ($count > 1) {
			$part = array_shift($parts);
			if (\in_array($part, ['App', 'Enum', 'Enums'])) {
				continue;
			}

			$path .= \DIRECTORY_SEPARATOR . Str::studly($part);

			$count = \count($parts);
		}

		File::ensureDirectoryExists($path);

		return $path;
	}
}
