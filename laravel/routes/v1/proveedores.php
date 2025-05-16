<?php

use App\Http\Controllers\V1\ProveedorController;
use Illuminate\Support\Facades\Route;

        Route::get('proveedores', [ProveedorController::class, 'index']);
        Route::post('proveedores', [ProveedorController::class, 'store']);
        Route::get('proveedores/{id}', [ProveedorController::class, 'show']);
        Route::patch('proveedores/{id}', [ProveedorController::class, 'update']);
        Route::delete('proveedores/{id}', [ProveedorController::class, 'destroy']);
        Route::post('proveedores/cambiarEstado', [ProveedorController::class, 'cambiarEstado']);
        Route::get('proveedores-activos', [ProveedorController::class, 'activos']);

?>
