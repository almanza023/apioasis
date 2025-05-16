<?php

use App\Http\Controllers\V1\BodegaController;
use Illuminate\Support\Facades\Route;

        Route::get('bodegas', [BodegaController::class, 'index']);
        Route::post('bodegas', [BodegaController::class, 'store']);
        Route::get('bodegas/{id}', [BodegaController::class, 'show']);
        Route::patch('bodegas/{id}', [BodegaController::class, 'update']);
        Route::delete('bodegas/{id}', [BodegaController::class, 'destroy']);
        Route::post('bodegas/cambiarEstado', [BodegaController::class, 'cambiarEstado']);
        Route::get('bodegas-activos', [BodegaController::class, 'activos']);

?>
