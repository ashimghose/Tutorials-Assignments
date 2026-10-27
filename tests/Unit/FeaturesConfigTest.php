<?php

$modules = ['accounts', 'checkout', 'academic_orders', 'chat', 'live_classroom', 'recordings'];

test('defaults to phase 0 with every phase 2+ module off', function () use ($modules) {
    // phpunit.xml switches modules on for the feature tests; read the
    // config file as it behaves with none of them set.
    $names = ['APP_PHASE', ...array_map(fn ($module) => 'FEATURE_'.strtoupper($module), $modules)];
    $saved = [];

    foreach ($names as $name) {
        $saved[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
        unset($_ENV[$name], $_SERVER[$name]);
        putenv($name);
    }

    try {
        $features = require __DIR__.'/../../config/features.php';
    } finally {
        foreach ($saved as $name => [$env, $server, $process]) {
            if ($env !== null) {
                $_ENV[$name] = $env;
            }
            if ($server !== null) {
                $_SERVER[$name] = $server;
            }
            if ($process !== false) {
                putenv("{$name}={$process}");
            }
        }
    }

    expect($features['phase'])->toBe(0);

    foreach ($modules as $module) {
        expect($features[$module])->toBeFalse();
    }
});
