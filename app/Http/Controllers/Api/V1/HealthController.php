<?php

namespace App\Http\Controllers\Api\V1;

// T-01: controlador de salud. Responde {"status":"ok"} sin auth.
// En routes/api.php: Route::get('/health', [HealthController::class, 'show']);
class HealthController
{
    public function show(): array
    {
        return ['status' => 'ok'];
    }
}
