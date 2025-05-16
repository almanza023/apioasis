<?php

use App\Http\Controllers\V1\CategoriaController;
use Illuminate\Support\Facades\Route;

        Route::get('categorias', [CategoriaController::class, 'index']);
        Route::post('categorias', [CategoriaController::class, 'store']);
        Route::get('categorias/{id}', [CategoriaController::class, 'show']);
        Route::patch('categorias/{id}', [CategoriaController::class, 'update']);
        Route::delete('categorias/{id}', [CategoriaController::class, 'destroy']);
        Route::post('categorias/cambiarEstado', [CategoriaController::class, 'cambiarEstado']);
        Route::get('categorias-activos', [CategoriaController::class, 'activos']);

?>
