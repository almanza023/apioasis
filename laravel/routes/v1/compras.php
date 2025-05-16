<?php

use App\Http\Controllers\V1\CompraController;
use Illuminate\Support\Facades\Route;

        Route::get('compras', [CompraController::class, 'index']);
        Route::post('compras', [CompraController::class, 'store']);
        Route::post('compras-detalles', [CompraController::class, 'storeDetalles']);
        Route::get('compras/{id}', [CompraController::class, 'show']);
        Route::patch('compras/{id}', [CompraController::class, 'update']);
        Route::delete('compras/{id}', [CompraController::class, 'destroy']);
        Route::post('compras/cambiarEstado', [CompraController::class, 'cambiarEstado']);
        Route::get('compras-activos', [CompraController::class, 'activos']);
        Route::delete('compras-detalles/{id}', [CompraController::class, 'destroyDetalle']);
        Route::post('compras-filter', [CompraController::class, 'filter']);


?>
