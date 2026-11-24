<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

/**
 * Only rules covering deprecations/breaking changes up to PHP 8.4.
 * No code-style refactors on purpose.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRules([
        ExplicitNullableParamTypeRector::class,
        NullToStrictStringFuncCallArgRector::class,
        Utf8DecodeEncodeToMbConvertEncodingRector::class,
        VariableInStringInterpolationFixerRector::class,
    ]);
