<?php
// T-01 evidencia: GET /health -> 200 {"status":"ok"}
// Ejecutar con Docker (sin PHP local): docker run --rm -v ${PWD}:/var/www/api -w /var/www/api php:8.3-cli php public/index.php
// Contrato: spec-laravel/api-contract.md §3.1, Tareas T-01.

it('health responds ok', function () {
    $_SERVER['REQUEST_URI'] = '/health';
    ob_start();
    include __DIR__ . '/../../public/index.php';
    $body = ob_get_clean();
    expect(json_decode($body, true))->toBe(['status' => 'ok']);
});
