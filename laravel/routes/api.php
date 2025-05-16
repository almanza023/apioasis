<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    //Prefijo V1, todo lo que este dentro de este grupo se accedera escribiendo v1 en el navegador, es decir /api/v1/*

    require __DIR__ . '/v1/auth.php';

    //Route::group(['middleware' => ['jwt.verify']], function() {
    //Todo lo que este dentro de este grupo requiere verificaci��n de usuario.

    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('cambiar-clave', [AuthController::class, 'cambiarClave']);
    Route::post('get-user', [AuthController::class, 'getUser']);
    //equipos
    require __DIR__ . '/v1/categorias.php';
    //jugadores
    require __DIR__ . '/v1/clientes.php';
    //partidos
    require __DIR__ . '/v1/gastos.php';
    //incidencias
    require __DIR__ . '/v1/mesa.php';
    //fixture
    require __DIR__ . '/v1/productos.php';
    //resultadopartidos
    require __DIR__ . '/v1/proveedores.php';
    //resultadopartidos
    require __DIR__ . '/v1/usuarios.php';
    //resultadopartidos
    require __DIR__ . '/v1/tipo-gastos.php';
    //tipopartidos
    require __DIR__ . '/v1/tipo-pagos.php';
    require __DIR__ . '/v1/apertura-caja.php';
    require __DIR__ . '/v1/ventas.php';
    require __DIR__ . '/v1/compras.php';
    require __DIR__ . '/v1/bodegas.php';
    require __DIR__ . '/v1/empresas.php';
    require __DIR__ . '/v1/cartera.php';
    require __DIR__ . '/v1/cartera-compras.php';
    //});
});
