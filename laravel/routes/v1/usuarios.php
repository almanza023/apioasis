<?php

use App\Http\Controllers\V1\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('usuarios', [UsuarioController::class, 'index']);
Route::post('usuarios', [UsuarioController::class, 'store']);
Route::get('usuarios/{id}', [UsuarioController::class, 'show']);
Route::put('usuarios/{id}', [UsuarioController::class, 'update']);
Route::delete('usuarios/{id}', [UsuarioController::class, 'destroy']);
Route::get('usuarios-activos', [UsuarioController::class, 'activos']);
Route::post('usuarios/cambiarEstado', [UsuarioController::class, 'cambiarEstado']);


?>
