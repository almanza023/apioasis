<?php

use App\Http\Controllers\V1\CarteraCompraController;
use Illuminate\Support\Facades\Route;

        Route::get('carteras-compras', [CarteraCompraController::class, 'index']);
        Route::post('carteras-compras', [CarteraCompraController::class, 'store']);
        Route::get('carteras-compras/{id}', [CarteraCompraController::class, 'show']);
        Route::patch('carteras-compras/{id}', [CarteraCompraController::class, 'update']);
        Route::delete('carteras-compras/{id}', [CarteraCompraController::class, 'destroy']);
        Route::post('carteras-compras/cambiarEstado', [CarteraCompraController::class, 'cambiarEstado']);
        Route::post('carteras-compras-pagos', [CarteraCompraController::class, 'storePagos']);
        Route::get('carteras-compras-activos', [CarteraCompraController::class, 'activos']);
        Route::post('carteras-compras-filter', [CarteraCompraController::class, 'filter']);

?>
