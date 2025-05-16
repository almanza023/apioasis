<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\CarteraCompra;
use App\Models\PagoCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class CarteraCompraController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = CarteraCompra::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todos los carteras
        $carteras = $this->model::getAll();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $carteras
        ], Response::HTTP_OK);
    }

    public function storePagos(Request $request)
    {
        // Validamos los datos
        $data = $request->only('cartera_compra_id', 'fecha', 'tipo_pago_id', 'valor', 'observaciones', );
        $validator = Validator::make($data, [
            'cartera_compra_id' => 'required|exists:cartera_compras,id',
            'fecha' => 'required|date',
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'valor' => 'required|numeric|min:0',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $cartera = CarteraCompra::find($request->cartera_compra_id);
        if($request->valor > $cartera->saldo){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'El valor no puede ser mayor al saldo',
            ], Response::HTTP_OK);
        }

        // Protegemos la operación dentro de una transacción
        DB::transaction(function () use ($request) {
            // Creamos el gasto en la BD

            $cartera = CarteraCompra::find($request->cartera_compra_id);

            $pago = PagoCompra::create([
                'cartera_compra_id' => $request->cartera_compra_id,
                'fecha' => $request->fecha,
                'tipo_pago_id' => $request->tipo_pago_id,
                'valor' => $request->valor,
                'observaciones' => $request->observaciones,
                'estado' => 1,
            ]);


            $cartera->update([
                'saldo' => $cartera->saldo - $request->valor,
                'abonos' => $cartera->abonos + $request->valor,
            ]);
            $cartera->detalles()->create([
                'cartera_compra_id' => $cartera->id,
                'total' => $request->valor,
                'saldo' => $cartera->saldo,
                'abono' => $request->valor,
                'fecha' => $request->fecha,
                'estado' => 1,
            ]);

        });
         // Respuesta en caso de que todo vaya bien
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Pago Creado Exitosamente',
        ], Response::HTTP_OK);
    }


    public function store(Request $request)
    {
        // Validamos los datos
        $data = $request->only('proveedor_id', 'fecha', 'total', 'saldo', 'abonos', 'observaciones', );
        $validator = Validator::make($data, [
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha' => 'required|date',
            'total' => 'required|numeric|min:0',
            'saldo' => 'required|numeric|min:0',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
// Validamos que el saldo no sea mayor al total
        if ($request->saldo > $request->total) {
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'El saldo no puede ser mayor al total',
            ], Response::HTTP_OK);
        }

        // Protegemos la operación dentro de una transacción
        DB::transaction(function () use ($request) {
            // Creamos el gasto en la BD
            $cartera = CarteraCompra::create([
                'proveedor_id' => $request->proveedor_id,
                'fecha' => $request->fecha,
                'total' => $request->total,
                'saldo' => $request->saldo,
                'abonos' => $request->abonos,
                'observaciones' => $request->observaciones,
                'estado' => 1,
            ]);
            $cartera->detalles()->create([
                'cartera_id' => $cartera->id,
                'total' => $request->total,
                'saldo' => $request->saldo,
                'abono' => $request->abonos,
                'fecha' => $request->fecha,
                'estado' => 1,
            ]);

        });
         // Respuesta en caso de que todo vaya bien
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cartera Creada Exitosamente',
        ], Response::HTTP_OK);
    }


    public function show($id)
    {
        // Buscamos el gasto
        $cartera = $this->model::with(['proveedor', 'detalles', 'pagos', 'pagos.tipoPago'])->find($id);
        // Si el gasto no existe devolvemos error no encontrado
        if (!$cartera) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Cartera no encontrada'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $cartera
        ], Response::HTTP_OK);
    }
    public function update(Request $request, $id)
    {
        // Validación de datos
        $data = $request->only('proveedor_id', 'fecha', 'total', 'saldo', 'abonos', 'observaciones');
        $validator = Validator::make($data, [
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha' => 'required|date',
            'total' => 'required|numeric|min:0',
            'saldo' => 'required|numeric|min:0',
            'abonos' => 'required|numeric|min:0',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el gasto
        $cartera = $this->model::findOrFail($id);
        // Actualizamos el gasto.
        $cartera->update([
            'proveedor_id' => $request->proveedor_id,
            'fecha' => $request->fecha,
            'total' => $request->total,
            'saldo' => $request->saldo,
            'abonos' => $request->abonos,
            'observaciones' => $request->observaciones,
        ]);
        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $request->total,
            'saldo' => $request->saldo,
            'abono' => $request->abonos,
            'fecha' => $request->fecha,
            'estado' => 1,
        ]);

        // Respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cartera Actualizada Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Buscamos el gasto
        $cartera = $this->model::findOrFail($id);

        // Eliminamos el gasto
        $cartera->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cartera Eliminada Exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un gasto (Activo/Inactivo)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cambiarEstado(Request $request)
    {
        // Validación de datos
        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required'
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el gasto
        $cartera = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $cartera->estado = ($cartera->estado == 1) ? 2 : 1;
        $cartera->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado de la Cartera Actualizada Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar todos los gastos activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function activos()
    {
        // Listamos todos los registros activos
        $gastos = $this->model::where('estado', 1)->get();
        if ($gastos) {
            return response()->json([
                'code' => 200,
                'data' => $gastos
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function filter (Request $request){

        $data = $request->only('proveedor_id','estado');

        $carteras = $this->model::filter($request->proveedor_id,$request->estado);

        if ($carteras) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Carteras filtradas Exitosamente',
                'data' => $carteras
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'No se encontraron carteras',
                'data' => []
            ], Response::HTTP_OK);
        }

    }


}
