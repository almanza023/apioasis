<?php

use App\Http\Controllers\V1\CarteraController;
use Illuminate\Support\Facades\Route;

        Route::get('carteras', [CarteraController::class, 'index']);
        Route::post('carteras', [CarteraController::class, 'store']);
        Route::get('carteras/{id}', [CarteraController::class, 'show']);
        Route::patch('carteras/{id}', [CarteraController::class, 'update']);
        Route::delete('carteras/{id}', [CarteraController::class, 'destroy']);
        Route::post('carteras/cambiarEstado', [CarteraController::class, 'cambiarEstado']);
        Route::post('carteras-pagos', [CarteraController::class, 'storePagos']);
        Route::get('carteras-activos', [CarteraController::class, 'activos']);
        Route::post('carteras-filter', [CarteraController::class, 'filter']);

?>
