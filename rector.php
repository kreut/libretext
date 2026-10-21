<?php

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/tests'])
    ->withSets([PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES])
    ->withImportNames(importShortClasses: false);
