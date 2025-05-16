<?php

use App\Http\Controllers\V1\TipoGastoController;
use Illuminate\Support\Facades\Route;

        Route::get( 'tipo-gastos', [TipoGastoController::class, 'index']);
        Route::post('tipo-gastos', [TipoGastoController::class, 'store']);
        Route::get('tipo-gastos/{id}', [TipoGastoController::class, 'show']);
        Route::patch('tipo-gastos/{id}', [TipoGastoController::class, 'update']);
        Route::delete('tipo-gastos/{id}', [TipoGastoController::class, 'destroy']);
        Route::post('tipo-gastos/cambiarEstado', [TipoGastoController::class, 'cambiarEstado']);
        Route::get('tipo-gastos-activos', [TipoGastoController::class, 'activos']);

?>
