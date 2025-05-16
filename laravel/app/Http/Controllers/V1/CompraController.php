<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\CajaMenor;
use App\Models\CarteraCompra;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoBodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class CompraController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = Compra::class;
        // $token = $request->header('Authorization');
        // if($token != '')
        //     //En caso de que requiera autentifiación la ruta obtenemos el usuario y lo almacenamos en una variable, nosotros no lo utilizaremos.
        //     $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todas las mesas
        $objeto = $this->model::getAll();
        if ($objeto) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validamos los datos
        $data = $request->only( 'user_id', 'proveedor_id', 'fecha', 'forma_pago', 'bodega_id');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'proveedor_id' => 'required',
            'bodega_id' => 'required',
            'forma_pago' => 'required',
            'fecha' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Creamos la mesa en la BD
        $objeto = $this->model::create([
            'user_id' => $request->user_id,
            'proveedor_id' => $request->proveedor_id,
            'bodega_id' => $request->bodega_id,
            'forma_pago' => $request->forma_pago,
            'fecha' => $request->fecha
        ]);

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Compra Creado Exitosamente',
            'data'=>$objeto
        ], Response::HTTP_OK);
    }

    public function storeDetalles(Request $request)
    {
        // Validamos los datos
        $data = $request->only('compra_id', 'producto_id', 'precio', 'precio_venta', 'cantidad');
        $validator = Validator::make($data, [
            'compra_id' => 'required',
            'producto_id' => 'required',
            'precio' => 'required',
            'precio_venta' => 'required',
            'cantidad' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $subtotal=0;
        $subtotal=($request->precio * $request->cantidad);


            DetalleCompra::create([
                'compra_id' => $request->compra_id,
                'producto_id' => $request->producto_id,
                'cantidad' => $request->cantidad,
                'precio' => $request->precio,
                'precio_venta' => $request->precio_venta,
                'subtotal' => $subtotal
            ]);

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Agregado Exitosamente',
            'data'=>[]
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Mesa  $mesa
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Buscamos la mesa
        $objeto = $this->model::find($id);
        $data=[];

        // Si la mesa no existe devolvemos error no encontrado
        if (!$objeto) {

            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'Registro no encontrado en la base de datos.'
            ], 404);
        }

        $detalles=DetalleCompra::getDetalleByCompra($objeto->id);
        $data=[
            'compra'=>$objeto,
            'detalles'=>$detalles
        ];

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $data
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Mesa  $mesa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validación de datos
        $data = $request->only('total', 'cantidad', 'user_id', 'forma_pago');
        $validator = Validator::make($data, [
            'total' => 'required',
            'forma_pago' => 'required',
            'user_id' => 'required',
            'cantidad' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);
        if($objeto){
            try {
                DB::beginTransaction();
                // $topeCajaMenor=CajaMenor::getDisponibleCaja();
                //     if($request->total > $topeCajaMenor){
                //         return response()->json([
                //             'code' => 400,
                //             'isSuccess' => false,
                //             'message' => 'El Valor del gasto Supera el Valor Disponible en Caja ',
                //         ], Response::HTTP_OK);
                //     }
                $objeto->update([
                    'total' => $request->total,
                    'cantidad' => $request->cantidad,
                    'estado'=>2
                ]);

                $detalles=DetalleCompra::getDetalleByCompra($objeto->id);
                if(count($detalles)>0){
                    foreach($detalles as $detalle) {
                        // Crear movimiento de inventario
                        // Obtener el último movimiento del producto
                        $ultimoMovimiento = MovimientoInventario::where('producto_id', $detalle->producto_id)
                            ->orderBy('id', 'desc')
                            ->first();

                        $saldoAnterior = $ultimoMovimiento ? $ultimoMovimiento->saldo : 0;
                        $nuevoSaldo = $saldoAnterior + $detalle->total_cantidad;

                        MovimientoInventario::create([
                            'producto_id' => $detalle->producto_id,
                            'user_id' => $request->user_id,
                            'cantidad' => $detalle->total_cantidad,
                            'precio_venta' => $detalle->precio_venta,
                            'precio_compra' => $detalle->precio,
                            'tipo' => '1',
                            'descripcion' => 'ENTRADA POR COMPRA #' . $objeto->id,
                            'fecha' => $objeto->fecha,
                            'saldo' => $nuevoSaldo
                        ]);

                        // Actualizar saldo del producto
                        $producto = Producto::find($detalle->producto_id);
                        if($producto->precio != $detalle->precio_venta){
                            $producto->precio=$detalle->precio_venta;
                        }

                        $producto->stock_actual += $detalle->total_cantidad;
                        $producto->save();

                        $productoBodegas=ProductoBodega::updateCantidadByProductoAndBodega($producto->id, $objeto->bodega_id, $detalle->total_cantidad,2);
                        CajaMenor::create([
                            'fecha'=>now(),
                            'salidas'=>$request->total,
                            'descripcion'=>'SALIDA POR COMPRA #' . $objeto->id,
                        ]);

                         //Forma de Compra CREDITO
                        if($request->forma_pago==2){
                            $cartera=CarteraCompra::verificarCartera($objeto->proveedor_id, $objeto->total, $objeto->id);
                        }

                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            // Devolvemos los datos actualizados.
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Compra Finalizada Exitosamente',
            ], Response::HTTP_OK);


        }else{
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'No se encontro el número del Pedido',
            ], Response::HTTP_OK);
        }



    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Mesa  $mesa
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);

        // Eliminamos la mesa
        $objeto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Mesa Eliminada Exitosamente'
        ], Response::HTTP_OK);
    }

    public function destroyDetalle($id)
    {
        // Buscamos la mesa
        $objeto = DetalleCompra::findOrFail($id);

        // Eliminamos la mesa
        $objeto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Eliminado Exitosamente'
        ], Response::HTTP_OK);
    }

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

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $objeto->estado = 2;
        $objeto->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    public function activos()
    {
        // Listamos todos los registros activos
        $objeto = $this->model::where('estado', 1)->get();
        if ($objeto) {
            return response()->json([
                'code' => 200,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function filter(Request $request)
    {
        // Listamos todas las mesas
        $objeto = $this->model::getFilter($request->estado, $request->proveedor_id, $request->fecha_inicio,
            $request->fecha_fin);
        if ($objeto) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

}
