<?php

use App\Http\Controllers\V1\MesaController;
use Illuminate\Support\Facades\Route;

        Route::get('mesas', [MesaController::class, 'index']);
        Route::post('mesas', [MesaController::class, 'store']);
        Route::get('mesas/{id}', [MesaController::class, 'show']);
        Route::patch('mesas/{id}', [MesaController::class, 'update']);
        Route::delete('mesas/{id}', [MesaController::class, 'destroy']);
        Route::post('mesas/cambiarEstado', [MesaController::class, 'cambiarEstado']);
        Route::get('mesas-activos', [MesaController::class, 'activos']);
        Route::get('mesas-pedidos-activos', [MesaController::class, 'mesasConPedidoActivo']);

?>
