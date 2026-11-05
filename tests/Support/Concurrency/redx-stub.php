<?php

/*
 * Test-only stand-in for RedX's parcel API (GET /parcel/info/{tracking}). It serves the status the test wrote into
 * storage/framework/testing/redx-stub.json for that tracking number. It is not RedX and is never used outside tests.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$map = is_file(__DIR__.'/../../../storage/framework/testing/redx-stub.json')
    ? json_decode((string) file_get_contents(__DIR__.'/../../../storage/framework/testing/redx-stub.json'), true) ?: []
    : [];

if (preg_match('#^/parcel/info/(.+)$#', (string) $path, $match) && isset($map[$match[1]])) {
    header('Content-Type: application/json');
    echo json_encode(['parcel' => ['status' => $map[$match[1]]]]);

    return;
}

http_response_code(404);
header('Content-Type: application/json');
echo '{}';
