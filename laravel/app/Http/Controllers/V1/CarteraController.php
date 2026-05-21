<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Cartera;
use App\Models\Operacion;
use App\Models\Pago;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class CarteraController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = Cartera::class;
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
        $data = $request->only('cartera_id', 'venta_id', 'fecha', 'tipo_pago_id', 'valor', 'observaciones', 'caja_id' );
        $validator = Validator::make($data, [
            'cartera_id' => 'required|exists:cartera,id',
            'venta_id' => 'nullable|exists:ventas,id',
            'fecha' => 'required|date',
            'caja_id' => 'required',
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'valor' => 'required|numeric|min:0',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $cartera = Cartera::find($request->cartera_id);
        if (!$cartera) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Cartera no encontrada',
            ], Response::HTTP_OK);
        }

        $venta = null;
        if ($request->filled('venta_id')) {
            $venta = Venta::where('id', $request->venta_id)
                ->where('cliente_id', $cartera->cliente_id)
                ->first();

            if (!$venta) {
                return response()->json([
                    'code' => 400,
                    'isSuccess' => false,
                    'message' => 'La venta no corresponde al cliente de la cartera',
                ], Response::HTTP_OK);
            }

            $saldoVenta = ($venta->saldo === null || $venta->saldo === '')
                ? (float) $venta->total
                : (float) $venta->saldo;

            if ($request->valor > $saldoVenta) {
                return response()->json([
                    'code' => 400,
                    'isSuccess' => false,
                    'message' => 'El valor no puede ser mayor al saldo de la venta',
                ], Response::HTTP_OK);
            }
        }

        if($request->valor > $cartera->saldo){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'El valor no puede ser mayor al saldo',
            ], Response::HTTP_OK);
        }

        // Protegemos la operación dentro de una transacción
        DB::transaction(function () use ($request, $venta) {
            // Creamos el gasto en la BD

            $cartera = Cartera::find($request->cartera_id);
            $fecha = Carbon::parse($request->fecha)->format('Y-m-d');
            $pago = Pago::create([
                'cartera_id' => $request->cartera_id,
                'venta_id' => $request->venta_id,
                'fecha' => $fecha,
                'caja_id' => $request->caja_id,
                'tipo_pago_id' => $request->tipo_pago_id,
                'valor' => $request->valor,
                'observaciones' => $request->observaciones,
                'estado' => 1,
            ]);


            $cartera->update([
                'saldo' => $cartera->saldo - $request->valor,
                'abonos' => $cartera->abonos + $request->valor,
            ]);

            if ($venta) {
                $this->actualizarVentaPorPago($venta, (float) $request->valor);
            }

            $cartera->detalles()->create([
                'cartera_id' => $cartera->id,
                'total' => $cartera->total,
                'saldo' => $cartera->saldo,
                'abono' => $request->valor,
                'fecha' => $fecha,
                'estado' => 1,
            ]);

            //Cerrar la Cartera
            if($cartera->saldo == 0){
                $cartera->update([
                    'estado' => 2,
                ]);
            }

            //Registrar la Operacion
            $operacion=Operacion::updateOrCreate(
                ['tipo_operacion_id' => 3, 'numero' => $pago->id],
                [
                    'fecha' => $fecha,
                    'estado' => 1,
                ]
            );

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
        $data = $request->only('cliente_id', 'fecha', 'total', 'saldo', 'abonos', 'observaciones', );
        $validator = Validator::make($data, [
            'cliente_id' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'total' => 'required|numeric|min:0',
            'saldo' => 'required|numeric|min:0',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
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

            $cartera=Cartera::where('cliente_id',$request->cliente_id)
            ->where('estado',1)
            ->first();
            if($cartera){
                $cartera->update([
                    'total' => $cartera->total + $request->total,
                    'saldo' => ($cartera->total + $request->total)- ($cartera->abonos + $request->abonos),
                    'abonos' => $cartera->abonos + $request->abonos,
                ]);
                $cartera->detalles()->create([
                    'cartera_id' => $cartera->id,
                    'total' => $request->total,
                    'saldo' => $request->saldo,
                    'saldoinicial' => $cartera->total,
                    'abono' => $request->abonos,
                    'fecha' => $request->fecha,
                    'estado' => 1,
                ]);
            }else{
                $cartera = Cartera::create([
                    'cliente_id' => $request->cliente_id,
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
            }



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
        //Actualizamos la Cartera
        $this->model::actualizarCartera($id);
        // Buscamos el gasto
        $cartera = $this->model::with(['cliente', 'detalles', 'pagos', 'pagos.tipoPago'])->find($id);

        // Evita N+1: actualiza en bloque los saldos vacios con el total de la venta.
        Venta::where('cliente_id', $cartera->cliente_id)
            ->where('forma_venta', 2)
            ->where('cartera_id', $cartera->id)
            ->where('estado', 1)
            ->where(function ($query) {
                $query->whereNull('abono')
                    ->orWhere('abono', 0);
            })
            ->update([
                'saldo' => DB::raw('total'),
                'abono' => null,
            ]);

        $ventas=Venta::select('id','fecha','total', 'abono','saldo')
        ->where('cliente_id',$cartera->cliente_id)
        ->where('forma_venta',2)
        ->where('cartera_id',$cartera->id)
        ->where('estado',1)
        ->where('saldo', '>', 0)
        ->orderBy('id','asc')
        ->get();



        if(!$ventas->count()){
            $ventas=Venta::select('id','fecha','total', 'abono','saldo')
            ->where('cliente_id',$cartera->cliente_id)
            ->where('forma_venta',2)
            ->where('estado',1)
            ->where(function ($query) {
                $query->where('saldo', '>', 0)
                    ->orWhereNull('saldo');
            })
            ->orderBy('id','asc')
            ->get();
        }
        $cartera->ventas=$ventas;


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
        $data = $request->only('cliente_id', 'fecha', 'total', 'saldo', 'abonos', 'observaciones');
        $validator = Validator::make($data, [
            'cliente_id' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'total' => 'required|numeric|min:0',
            'saldo' => 'required|numeric|min:0',
            'abonos' => 'required|numeric|min:0',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Buscamos el gasto
        $cartera = $this->model::findOrFail($id);
        // Actualizamos el gasto.
        $cartera->update([
            'cliente_id' => $request->cliente_id,
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
            return response()->json(['error' => $validator->errors()], 400);
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

        $data = $request->only('cliente_id','estado');

        $carteras = $this->model::filter($request->cliente_id,$request->estado);

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

    public function actualizarCartera (Request $request){

        $data = $request->only('cartera_id');

        $cartera = $this->model::actualizarCartera($request->cartera_id);

        if ($cartera) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Cartera actualizada Exitosamente',
                'data' => $cartera
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

    public function aplicarPagosFacturas(Request $request)
    {
        $data = $request->only('cartera_id');
        $validator = Validator::make($data, [
            'cartera_id' => 'required|exists:cartera,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $cartera = Cartera::find($request->cartera_id);
        if (!$cartera) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Cartera no encontrada',
            ], Response::HTTP_OK);
        }

        $totalPagos = (float) Pago::where('cartera_id', $cartera->id)->sum('valor');

        $ventas = Venta::where('cliente_id', $cartera->cliente_id)
            ->where('forma_venta', 2)
            ->where('estado', 1)
            ->where('cartera_id', $cartera->id)
            ->whereNull('abono')
            ->orderBy('fecha', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if (!$ventas->count()) {
            $ventas = Venta::where('cliente_id', $cartera->cliente_id)
                ->where('forma_venta', 2)
                ->where('estado', 1)
                ->whereNull('abono')
                ->orderBy('fecha', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        $totalFacturas = (float) $ventas->sum('total');
        $restante = $totalPagos;
        $facturasActualizadas = 0;

        DB::transaction(function () use ($ventas, &$restante, &$facturasActualizadas) {
            foreach ($ventas as $venta) {
                if ($restante <= 0) {
                    break;
                }

                $totalVenta = (float) $venta->total;
                $abonoAplicado = min($totalVenta, max(0, $restante));
                $saldoPendiente = max(0, $totalVenta - $abonoAplicado);

                $venta->update([
                    'abono' => $abonoAplicado,
                    'saldo' => $saldoPendiente,
                ]);

                $restante -= $abonoAplicado;
                $facturasActualizadas++;
            }
        });

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Pagos aplicados a facturas exitosamente',
            'data' => [
                'cartera_id' => $cartera->id,
                'total_pagos' => $totalPagos,
                'total_facturas' => $totalFacturas,
                'restante_sin_aplicar' => max(0, $restante),
                'facturas_actualizadas' => $facturasActualizadas,
            ],
        ], Response::HTTP_OK);
    }

public function destroyPago($id)
{
    // Protegemos la operación dentro de una transacción
    DB::transaction(function () use ($id) {
        // Buscamos el pago
        $pago = Pago::findOrFail($id);

        // Actualizamos la cartera para revertir el pago
        $cartera = Cartera::find($pago->cartera_id);
        if ($cartera) {
            $cartera->update([
                'saldo' => $cartera->saldo + $pago->valor,
                'abonos' => $cartera->abonos - $pago->valor,
                'observaciones' => 'Eliminación de Pago N° '.$pago->id,
            ]);

            $cartera->detalles()->create([
                'cartera_id' => $cartera->id,
                'total' => $cartera->total,
                'saldo' => $cartera->saldo,
                'abono' => $cartera->abonos,
                'fecha' => now(),
                'observaciones' => 'Eliminación de Pago N° '.$pago->id,
                'estado' => 1,
            ]);
        }

        if ($pago->venta_id) {
            $venta = Venta::find($pago->venta_id);
            if ($venta) {
                $this->actualizarVentaPorPago($venta, (float) $pago->valor, true);
            }
        }

        // Eliminamos el pago
        $pago->delete();
    });

    // Devolvemos la respuesta
    return response()->json([
        'code' => 200,
        'isSuccess' => true,
        'message' => 'Pago Eliminado Exitosamente'
    ], Response::HTTP_OK);
}

    private function actualizarVentaPorPago(Venta $venta, float $valor, bool $revertir = false): void
    {
        $venta->update([
            'abono' => $revertir ? max(0, $venta->abono - $valor) : $venta->abono + $valor,
            'saldo' => $revertir ? $venta->saldo + $valor : max(0, $venta->saldo - $valor),
        ]);
    }

}
