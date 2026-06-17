<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'admin', // ✅ Default guard set to admin
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        // ✅ For Admins (User table)
        'admin' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Optional Web (if frontend login needed)
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // ✅ For Customers (Customer table)
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // Admin / Users table
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Customers table
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Customer::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Configurations
    |--------------------------------------------------------------------------
    */
'passwords' => [
    // ✅ Admin / User password reset
    'admins' => [
        'provider' => 'users', // একই ইউজার টেবিল ব্যবহার করবে
        'table' => 'password_resets',
        'expire' => 60,
        'throttle' => 60,
    ],

    // ✅ Customer password reset
    'customers' => [
        'provider' => 'customers',
        'table' => 'password_resets',
        'expire' => 60,
        'throttle' => 60,
    ],
],


    'password_timeout' => 10800,

];
