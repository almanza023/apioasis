<?php

use App\Http\Controllers\V1\OperacionController;
use Illuminate\Support\Facades\Route;

        Route::get('operaciones', [OperacionController::class, 'index']);
        Route::post('operaciones', [OperacionController::class, 'store']);
        Route::get('operaciones/{id}', [OperacionController::class, 'show']);
        Route::patch('operaciones/{id}', [OperacionController::class, 'update']);
        Route::delete('operaciones/{id}', [OperacionController::class, 'destroy']);
        Route::post('operaciones/cambiarEstado', [OperacionController::class, 'cambiarEstado']);
        Route::get('operaciones-activos', [OperacionController::class, 'activos']);
        Route::post('operaciones-dia', [OperacionController::class, 'getOperacionesDia']);

?>
