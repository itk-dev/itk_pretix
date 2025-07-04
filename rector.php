<?php

/**
 * @file
 */

declare(strict_types=1);

use DrupalRector\Set\Drupal10SetList;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/src',
    __DIR__ . '/tests',
  ])
  ->withSkip([
    __DIR__ . '/src/ProxyClass',
  ])
  ->withSets([
    Drupal10SetList::DRUPAL_10,
  ])
  ->withPhpSets(php83: TRUE)
  ->withTypeCoverageLevel(0)
  ->withSkip([
    FirstClassCallableRector::class => [
      __DIR__ . '/src/Plugin/Field/FieldWidget/PretixDateWidget.php',
    ]
  ]);
