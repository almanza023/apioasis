<?php

use App\Http\Controllers\V1\GastoController;
use Illuminate\Support\Facades\Route;

        Route::get('gastos', [GastoController::class, 'index']);
        Route::post('gastos', [GastoController::class, 'store']);
        Route::get('gastos/{id}', [GastoController::class, 'show']);
        Route::patch('gastos/{id}', [GastoController::class, 'update']);
        Route::delete('gastos/{id}', [GastoController::class, 'destroy']);
        Route::post('gastos/cambiarEstado', [GastoController::class, 'cambiarEstado']);
        Route::get('gastos-activos', [GastoController::class, 'activos']);
        Route::post('gastos-filter', [GastoController::class, 'getFilterFechas']);

?>
