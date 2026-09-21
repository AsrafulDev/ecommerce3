<?php

/**
 * PHPUnit bootstrap.
 *
 * This shell exports the full .env (APP_ENV=local, DB_DATABASE=ecommerce3, ...)
 * as real environment variables. Laravel's Env repository is immutable and
 * consults $_SERVER before _.env files, so neither phpunit.xml's <env> entry nor
 * .env.testing could override them: tests silently ran against the LIVE
 * ecommerce3 database and RefreshDatabase migrate:fresh'd it.
 *
 * Pinning the values here (before vendor/autoload.php loads Laravel) guarantees
 * tests always target the dedicated test database.
 */
$overrides = [
    'APP_ENV'       => 'testing',
    'DB_CONNECTION' => 'mysql',
    'DB_DATABASE'   => 'ecommerce3_test',
];

foreach ($overrides as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
