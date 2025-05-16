<?php

use App\Http\Controllers\V1\ProductoController;
use Illuminate\Support\Facades\Route;

        Route::get('productos', [ProductoController::class, 'index']);
        Route::post('productos', [ProductoController::class, 'store']);
        Route::get('productos/{id}', [ProductoController::class, 'show']);
        Route::patch('productos/{id}', [ProductoController::class, 'update']);
        Route::delete('productos/{id}', [ProductoController::class, 'destroy']);
        Route::post('productos/cambiarEstado', [ProductoController::class, 'cambiarEstado']);
        Route::get('productos-activos', [ProductoController::class, 'activos']);
        Route::post('productos-movimientos', [ProductoController::class, 'movimientosInventario']);
        Route::post('productos-laboratorio', [ProductoController::class, 'storeLaboratorio']);
        Route::get('productos-laboratorio', [ProductoController::class, 'getLaboratorios']);
        Route::post('productos-bodegas', [ProductoController::class, 'storeProductoBodega']);
        Route::get('productos-bodegas', [ProductoController::class, 'getBodegas']);
        Route::post('productos-proximos-avencer', [ProductoController::class, 'getProximosAVencer']);
        Route::get('productos-inventario', [ProductoController::class, 'getProductosInventario']);
        Route::post('productos-cargar', [ProductoController::class, 'storeImport']);
?>
