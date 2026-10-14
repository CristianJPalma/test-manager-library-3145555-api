<?php
// T-01: Esqueleto + GET /health -> {"status":"ok"}
// Router mínimo para php -S (infra/api). En Laravel real: routes/api.php -> HealthController.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/health' || $path === '/api/v1/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok']);
    return;
}

// Contrato base: /api/v1 (ver spec-laravel/api-contract.md §1)
header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode([
    'code' => 'NOT_FOUND',
    'message' => 'Recurso no encontrado.',
    'details' => [],
]);
