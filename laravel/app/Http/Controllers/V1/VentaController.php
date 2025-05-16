<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Models\Cartera;
use App\Models\DetalleVenta;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\ProductoBodega;
use App\Models\Venta;
use App\Models\VentaTipoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class VentaController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = Venta::class;
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
        $data = $request->only( 'user_id', 'cliente_id', 'fecha', 'forma_venta', 'especial');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'cliente_id' => 'required',
            'forma_venta' => 'required',
            'fecha' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $caja=AperturaCaja::getCajaAbierta();
        if(!$caja){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'No Existe una Apertura de Caja',
                'data'=>[]
            ], Response::HTTP_OK);
        }

        // Creamos la mesa en la BD
        $objeto = $this->model::create([
            'user_id' => $request->user_id,
            'cliente_id' => $request->cliente_id,
            'caja_id' => $caja->id,
            'fecha' => $request->fecha,
            'forma_venta'=>$request->forma_venta,
            'especial'=>$request->especial,
            'estado'=>0 // Pendiente
        ]);

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Venta Creada Exitosamente',
            'data'=>$objeto
        ], Response::HTTP_OK);
    }

    public function storeDetalles(Request $request)
    {
        // Validamos los datos
        $data = $request->only('venta_id', 'producto_id', 'precio', 'cantidad', 'descuento');
        $validator = Validator::make($data, [
            'venta_id' => 'required',
            'producto_id' => 'required',
            'precio' => 'required',
            'cantidad' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $caja=AperturaCaja::getCajaAbierta();
        if(!$caja){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'No Existe una Apertura de Caja',
                'data'=>[]
            ], Response::HTTP_OK);
        }

        $subtotal=0;
        $precio=0;
        if($request->precio<0){
            $subtotal=($request->precio * $request->cantidad)*(-1);
        }else{
            $subtotal=($request->precio * $request->cantidad);
            $precio=$request->precio;
        }

        if(!empty($request->descuento)){
            $precio=$request->descuento;
            $subtotal=($request->descuento * $request->cantidad);
        }

            DetalleVenta::create([
                'venta_id' => $request->venta_id,
                'producto_id' => $request->producto_id,
                'cantidad' => $request->cantidad,
                'precio' => $precio,
                'descuento' => $request->descuento,
                'subtotal' => $subtotal
            ]);
            $data=DetalleVenta::getDetalleByVenta($request->venta_id);
        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Agregado Exitosamente',
            'data'=>$data
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
        $pagos=[];
        $detalles=DetalleVenta::getDetalleByVenta($objeto->id);
        $pagos=VentaTipoPago::getPagosByVenta($objeto->id);
        $data=[
            'venta'=>$objeto,
            'cliente'=>$objeto->cliente,
            'detalles'=>$detalles,
            'pagos'=>$pagos,
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
        $data = $request->only('total', 'cantidad', 'pagos', 'dineroRecibido', 'cambio',
        'observaciones', 'especial', 'forma_venta');
        $validator = Validator::make($data, [
            'total' => 'required',
            'cantidad' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $especial=0;
        if($request->especial){
            $especial=1;
        }

        //Validar Apertura de Caja
        $caja=AperturaCaja::getCajaAbierta();
        if(!$caja){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'No Existe una Apertura de Caja',
                'data'=>[]
            ], Response::HTTP_OK);
        }
        $bodega_id=$caja->bodega_id;
        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);
        if($objeto){
            $realizado=false;
            try {
                DB::beginTransaction();
                $estado=1; // Confirmada
                $objeto->update([
                    'total' => $request->total,
                    'cantidad' => $request->cantidad,
                    'observaciones'=>$request->observaciones,
                    'especial'=>$especial,
                    'forma_venta'=>$request->forma_venta,
                    'estado'=>$estado
                ]);

                $detalles = DetalleVenta::getDetalleByVenta($objeto->id);
                $descripcion = "SALIDA POR VENTA N° ".$objeto->id;
                foreach ($detalles as $item) {
                    $productoId = $item->producto_id;
                    $precioVenta = $item->producto->precio;
                    $inventario = MovimientoInventario::modificarStock($productoId, $objeto->user_id,
                     $item->total_cantidad,
                    $precioVenta, 0, $descripcion, 2);
                    $productoBodegas=ProductoBodega::updateCantidadByProductoAndBodega($productoId, $bodega_id, $item->total_cantidad,1);
                }
                //Agregar Pagos
                // Procesamos los pagos solo si la forma de venta es 1 (contado)
                if ($request->forma_venta == 1) {
                    foreach ($request->pagos as $pago) {
                        // Validamos los datos de cada pago
                        $pagoValidator = Validator::make($pago, [
                            'tipopago_id' => 'required',
                            'valor' => 'required|numeric|min:0',
                        ]);
                        // Si falla la validación del pago, lanzamos una excepción
                        if ($pagoValidator->fails()) {
                            throw new \Exception(implode(", ", $pagoValidator->messages()->all()));
                        }

                        // Creamos el registro de pago
                        $objeto->pagos()->create([
                            'tipopago_id' => $pago['tipopago_id'],
                            'valor' => $pago['valor'],
                            'venta_id' => $objeto->id, // Asociamos el pago a la venta creada
                        ]);
                    }
                }

                //Forma de Venta CREDITO
                if($request->forma_venta==2 && $request->especial==0){
                    $cartera=Cartera::verificarCartera($objeto->cliente_id, $objeto->total, $objeto->id);
                }

                DB::commit();
                $realizado=true;
                if($realizado){
                    // Devolvemos los datos actualizados.
                return response()->json([
                    'code' => 200,
                    'isSuccess' => true,
                    'message' => 'Venta N° '.$objeto->id.' Finalizada Exitosamente',
                ], Response::HTTP_OK);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'code' => 500,
                    'isSuccess' => false,
                    'message' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
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
        $objeto = DetalleVenta::findOrFail($id);
        $detalles=DetalleVenta::getDetalleByVenta($objeto->venta_id);
        // Eliminamos la mesa
        $objeto->delete();
        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Detalle Eliminado Exitosamente',
            'data'=>$detalles
        ], Response::HTTP_OK);
    }

    public function cambiarEstado(Request $request)
    {
        // Validación de datos
        $data = $request->only('id', 'user_id', 'observaciones');
        $validator = Validator::make($data, [
            'id' => 'required',
            'user_id' => 'required',
            'observaciones'=>'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $objeto = $this->model::findOrFail($request->id);
        if($objeto->estado==0){
            $objeto->estado = 2;
            $objeto->observaciones=$request->observaciones;
            $objeto->save();

            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Factura N° ' . $objeto->id . ' ANULADA Exitosamente',
            ], Response::HTTP_OK);
        }
        // Cambiamos el estado
        if($objeto->estado==1){
            $detalles=$objeto->detalles;
            if(count($detalles)>0){
                MovimientoInventario::actualizarStockPorAnulacionVenta($request->id, $request->user_id);
                $objeto->estado = 2;
                $objeto->observaciones=$request->observaciones;
                $objeto->save();

                return response()->json([
                    'code' => 200,
                    'isSuccess' => true,
                    'message' => 'Factura N° ' . $objeto->id . ' DEVUELTA Exitosamente',
                ], Response::HTTP_OK);
            }

            if($objeto->forma_venta==2 && $objeto->especial==0){
                //Descontarde la Cartera Si credito y no es especial
                $cartera=Cartera::descontarCartera($objeto->cliente_id, $objeto->id, $objeto->total);
          }



        }else{
             // Devolvemos la respuesta
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'La Venta no se encuentra FACTURADA ',
            ], Response::HTTP_OK);
        }


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
        $objeto = $this->model::getFilter($request->fecha_inicio,
            $request->fecha_fin, $request->estado, $request->rol, $request->cliente_id);
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

    public function obtenerDatosEmpresa()
    {
        $empresa = Empresa::first();
        if ($empresa) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $empresa
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'No se encontraron datos de la empresa',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }
    }


}
