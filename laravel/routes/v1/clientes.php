<?php

use App\Http\Controllers\V1\ClienteController;
use Illuminate\Support\Facades\Route;

        Route::get('clientes', [ClienteController::class, 'index']);
        Route::post('clientes', [ClienteController::class, 'store']);
        Route::get('clientes/{id}', [ClienteController::class, 'show']);
        Route::patch('clientes/{id}', [ClienteController::class, 'update']);
        Route::delete('clientes/{id}', [ClienteController::class, 'destroy']);
        Route::post('clientes/cambiarEstado', [ClienteController::class, 'cambiarEstado']);
        Route::get('clientes-activos', [ClienteController::class, 'activos']);

?>
