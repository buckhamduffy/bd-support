<?php

use BuckhamDuffy\BdSupport\Support\DocGen;

require_once realpath(__DIR__ . '/vendor/autoload.php');

(new DocGen())->run(realpath(__DIR__ . '/src'));
