<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\ValueObject\Option;

/** @var \Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder $config */

$config = require __DIR__ . '/vendor/buckhamduffy/coding-standards/ecs.php';

$config
	->withParallel()
	->withSpacing(indentation: Option::INDENTATION_TAB)
	->withPaths([
		__DIR__ . '/src',
	]);

return $config;
