<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 🔒 LICENSE SERVER (HARDCODED — DO NOT EDIT OR REMOVE)
    |--------------------------------------------------------------------------
    |
    | These values are intentionally hardcoded and encoded for license
    | protection. Removing or editing them breaks the application
    | (see the integrity check in AppServiceProvider::boot()).
    |
    */

    // Mother license server URL (base64 of https://softmit.xyz)
    'api_url' => base64_decode('aHR0cHM6Ly9zb2Z0bWl0Lnh5eg=='),

    // Script name sent to the license server (base64 of "Ecommerce Pro")
    'script_name' => base64_decode('RWNvbW1lcmNlIFBybw=='),

    // Current installed version (base64 of 1.0.0)
    'current_version' => base64_decode('MS4wLjA='),

    // 🔒 Hard enforcement — CANNOT be disabled via .env.
    // The admin area requires a valid license (localhost/master are skipped).
    'enforce' => true,

];
