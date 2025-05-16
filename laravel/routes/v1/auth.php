<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'authenticate']);
Route::post('register', [AuthController::class, 'register']);
Route::post('user/actualizar', [AuthController::class, 'update']);
// Agrega aquí otras rutas relacionadas con la autenticación...

?>
