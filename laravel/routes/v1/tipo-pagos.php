<?php

use App\Http\Controllers\V1\TipoPagoController;
use Illuminate\Support\Facades\Route;

        Route::get( 'tipo-pagos', [TipoPagoController::class, 'index']);
        Route::post('tipo-pagos', [TipoPagoController::class, 'store']);
        Route::get('tipo-pagos/{id}', [TipoPagoController::class, 'show']);
        Route::patch('tipo-pagos/{id}', [TipoPagoController::class, 'update']);
        Route::delete('tipo-pagos/{id}', [TipoPagoController::class, 'destroy']);
        Route::post('tipo-pagos/cambiarEstado', [TipoPagoController::class, 'cambiarEstado']);
        Route::get('tipo-pagos-activos', [TipoPagoController::class, 'activos']);

?>
