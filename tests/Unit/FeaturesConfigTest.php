<?php

test('defaults to phase 0 with every phase 2+ module off', function () {
    $features = require __DIR__.'/../../config/features.php';

    expect($features['phase'])->toBe(0);

    foreach (['accounts', 'checkout', 'academic_orders', 'chat', 'live_classroom', 'recordings'] as $module) {
        expect($features[$module])->toBeFalse();
    }
});
