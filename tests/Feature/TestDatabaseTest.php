<?php

use Illuminate\Support\Facades\DB;

it('runs the tests against the dedicated test database', function () {
    expect(DB::connection()->getDatabaseName())->toBe('site_gerencial_test');
});
