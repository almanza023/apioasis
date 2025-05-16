<?php

use App\Http\Controllers\V1\CajaController;
use Illuminate\Support\Facades\Route;

        Route::get('apertura-caja', [CajaController::class, 'index']);
        Route::post('apertura-caja', [CajaController::class, 'store']);
        Route::get('apertura-caja/{id}', [CajaController::class, 'show']);
        Route::patch('apertura-caja/{id}', [CajaController::class, 'update']);
        Route::delete('apertura-caja/{id}', [CajaController::class, 'destroy']);
        Route::post('apertura-caja/cambiarEstado', [CajaController::class, 'cambiarEstado']);
        Route::get('apertura-caja-activos', [CajaController::class, 'activos']);
        Route::post('apertura-caja-dia', [CajaController::class, 'gerReporteDia']);
        Route::get('apertura-caja-abierta', [CajaController::class, 'getEstadoCaja']);
        Route::post('apertura-estadisticas', [CajaController::class, 'getEstadisticas']);
        Route::post('apertura-historicos', [CajaController::class, 'getReporteHistorico']);
        Route::post('apertura-caja-menor', [CajaController::class, 'getReporteHistoricoCajaMenor']);
        Route::post('reporte-caja', [CajaController::class, 'gerReporteDia']);
?>
