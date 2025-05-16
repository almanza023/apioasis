<?php



use App\Http\Controllers\V1\VentaController;
use Illuminate\Support\Facades\Route;

        Route::get('ventas', [VentaController::class, 'index']);
        Route::post('ventas', [VentaController::class, 'store']);
        Route::post('ventas-detalles', [VentaController::class, 'storeDetalles']);
        Route::get('ventas/{id}', [VentaController::class, 'show']);
        Route::patch('ventas/{id}', [VentaController::class, 'update']);
        Route::delete('ventas/{id}', [VentaController::class, 'destroy']);
        Route::post('ventas/cambiarEstado', [VentaController::class, 'cambiarEstado']);
        Route::get('ventas-activos', [VentaController::class, 'activos']);
        Route::post('ventas-mesa', [VentaController::class, 'getPedidoFechaMesa']);
        Route::post('ventas-filter', [VentaController::class, 'filter']);
        Route::get('ventas-empresa', [VentaController::class, 'obtenerDatosEmpresa']);
        Route::delete('ventas-detalles/{id}', [VentaController::class, 'destroyDetalle']);



?>
