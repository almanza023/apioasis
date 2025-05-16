<?php


use App\Http\Controllers\V1\EmpresaController;
use Illuminate\Support\Facades\Route;

        Route::get('empresas', [EmpresaController::class, 'index']);
        Route::post('empresas', [EmpresaController::class, 'store']);
        Route::get('empresas/{id}', [EmpresaController::class, 'show']);
        Route::patch('empresas/{id}', [EmpresaController::class, 'update']);
        Route::delete('empresas/{id}', [EmpresaController::class, 'destroy']);
        Route::post('empresas/cambiarEstado', [EmpresaController::class, 'cambiarEstado']);
        Route::get('empresas-activos', [EmpresaController::class, 'activos']);

?>
