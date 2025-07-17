<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Models\CajaMenor;
use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Pago;
use App\Models\PagoCompra;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class CajaController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = AperturaCaja::class;
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
        $data = $request->only('user_id','bodega_id', 'fecha','monto_inicial', 'descripcion');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'bodega_id' => 'required',
            'fecha' => 'required',
            'monto_inicial' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $fecha=$request->fecha;
        $cajas=AperturaCaja::where('estado', 1)->get();
        if(count($cajas)>0){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'Esta pendiente por CERRAR Caja. Por Favor Verificar',
            ], Response::HTTP_OK);
        }
        $validarFecha=AperturaCaja::validarAperturaFecha($fecha);
        if(count($validarFecha)>0){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'Ya se encuentra realizada la Apertura de Caja para la fecha '.$request->fecha,
            ], Response::HTTP_OK);
        }

        // Creamos la mesa en la BD
        $objeto = $this->model::create([
            'user_id'=>$request->user_id,
            'bodega_id'=>$request->bodega_id,
            'fecha'=>$request->fecha,
            'monto_inicial' => $request->monto_inicial,
            'descripcion' => $request->descripcion,
        ]);

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Apertura de Caja para la fecha '.$request->fecha." Exitosamente",
            "data"=>$objeto
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

        // Si la mesa no existe devolvemos error no encontrado
        if (!$objeto) {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'Registro no encontrado en la base de datos.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $objeto
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
        $data = $request->only('user_id', 'fecha_cierre', 'monto_final', 'totalventas',
        'totalgastos', 'totalabonos', 'totalpagoscompras', 'utilidad', 'ventasefectivo',
        'pagosefectivo', 'comprascontado');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'monto_final' => 'required',
            'totalventas' => 'required',
            'totalgastos' => 'required',
            'utilidad' => 'required',
        ]);

        $fecha_cierre = \Carbon\Carbon::parse($request->fecha_cierre)->format('Y-m-d');
        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);
        $ventasPendientes=Venta::where('estado',0)
        ->where('caja_id',$id)->count();
        if($ventasPendientes>0){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'Existen Ventas Pendientes por Facturar.',
            ], Response::HTTP_OK);
        }

        // Actualizamos la Caja
        $objeto->update([
            'user_id' => $request->user_id,
            'fecha_cierre' => $fecha_cierre,
            'monto_final' => $request->monto_final,
            'totalventas' => $request->totalventas,
            'totalgastos' => $request->totalgastos,
            'totalabonos' => $request->totalabonos,
            'totalpagocompras' => $request->totalpagoscompras,
            'utilidad' => $request->utilidad,
            'ventasefectivo' => $request->ventasefectivo,
            'pagosefectivo' => $request->pagosefectivo,
            'comprascontado' => $request->comprascontado,
            'estado' => 2,
        ]);

        // Insertar en caja menor
        $cajaMenor = CajaMenor::create([
            'fecha' => now(),
            'descripcion' => 'Entrada Por Cierre de caja #' . $objeto->id,
            'entradas' => $objeto->utilidad,
            'user_id' => $request->user_id,
        ]);


        // Devolvemos los datos actualizados.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cierre de Caja Realizado Exitosamente',
        ], Response::HTTP_OK);
    }

    public function updateCaja(Request $request)
    {
        // Validación de datos
        $data = $request->only('user_id','bodega_id', 'fecha','monto_inicial', 'descripcion', 'id');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'bodega_id' => 'required',
            'fecha' => 'required',
            'monto_inicial' => 'required',
        ]);
        $fecha = \Carbon\Carbon::parse($request->fecha)->format('Y-m-d');
        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($request->id);
        $ventasPendientes=Venta::where('estado',0)
        ->where('caja_id',$request->id)->count();
        if($ventasPendientes>0){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'Existen Ventas Pendientes por Facturar.',
            ], Response::HTTP_OK);
        }
        $monto_final=($request->monto_final + $request->totalventas + $request->totalabonos ) -
        ($request->totalgastos + $request->totalpagoscompras);
        // Actualizamos la Caja
        $objeto->update([
            'user_id' => $request->user_id,
            'bodega_id' => $request->bodega_id,
            'fecha' => $fecha,
            'monto_inicial' => $request->monto_inicial,
            'descripcion' => $request->descripcion,
        ]);

        // Devolvemos los datos actualizados.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Caja Actualizada Exitosamente',
        ], Response::HTTP_OK);
    }

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
            'message' => 'Caja  Eliminada Exitosamente'
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
        }        // Buscamos la mesa
        $objeto = $this->model::findOrFail($request->id);

        if($objeto->estado==2){
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'Ya se encuentra CERRADA la Caja no se puede ANULAR',
            ], Response::HTTP_OK);
        }

        // Cambiamos el estado
        $objeto->estado = 3;
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


    public function gerReporteDia(Request $request)
    {
        // Validación de datos
        $data = $request->only('fechaInicio', 'fechaFinal', 'caja_id');
        $validator = Validator::make($data, [
            'fechaInicio' => $request->caja_id ? 'nullable' : 'required',
            'fechaFinal' => $request->caja_id ? 'nullable' : 'required',
            'caja_id' => 'nullable',
        ]);
        if ($request->caja_id) {
            $fecha_inicio = null;
            $fecha_final = null;
        } else {
            $fecha_inicio = \Carbon\Carbon::parse($request->fechaInicio)->format('Y-m-d');
            $fecha_final = \Carbon\Carbon::parse($request->fechaFinal)->format('Y-m-d');
        }

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }        // Buscamos la mesa
        $data=[];
        if ($request->caja_id) {
            $caja = $this->model::find($request->caja_id);
        } else {
            $caja = $this->model::getCajaAbierta();
        }
        if($caja){
            $totalgastos=Gasto::getTotalByDate($fecha_inicio, $fecha_final, $caja->id);
            $totalventasGeneral=Venta::getTotalByDate($fecha_inicio, $fecha_final, $caja->id);
            $ventas=Venta::getVentasByDate($fecha_inicio, $fecha_final, $caja->id);
            $gastos=Gasto::getGastosByDate($fecha_inicio, $fecha_final, $caja->id);
            $comprasContado=Compra::getTotalByContado($caja->id);

            $pagos=Venta::getTotalByTipoPagoAndDate($fecha_inicio, $fecha_final, $caja->id);
            $totalabonosefectivo=Pago::getTotalByDate($caja->id);

            $pagosCompraEfectivo=PagoCompra::getTotalByDate($caja->id);

            $otrosmedios=0;
            $efectivo=0;
            foreach($pagos as $pago){
                if($pago->nombre=='EFECTIVO'){
                    $efectivo=$pago->total;
                }else{
                    $otrosmedios+=$pago->total;
                }
            }
            $totalneto=($caja->monto_inicial + $efectivo + $totalabonosefectivo ) -
            ($totalgastos + $pagosCompraEfectivo + $comprasContado);
            $estadoCaja = $caja->estado == 3 ? 'ANULADA' : ($caja->estado == 1 ? 'ABIERTA' : 'CERRADA');
            $data=[
                'caja_id'=>$caja->id,
                'estado_caja'=>$estadoCaja,
                'estado'=>$caja->estado,
                'fecha_inicio'=>$caja->fecha,
                'base_inicial'=>$caja->monto_inicial,
                'totalventasGeneral'=>$totalventasGeneral,
                'totalgastos'=>$totalgastos,
                'totalcomprascontado'=>$comprasContado,
                'totalneto'=>$totalneto,
                'totalabonosefectivo'=>$totalabonosefectivo,
                'totalpagoscompraefectivo'=>$pagosCompraEfectivo,
                'totalventascontado'=>$efectivo,
                'totalventasotrosmedios'=>$otrosmedios,
                'ventas'=>$ventas,
                'gastos'=>$gastos,
                'pagos'=>$pagos,
            ];
        }
        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data'=>$data,
        ], Response::HTTP_OK);
    }

    public function getEstadoCaja()
    {
        $caja = $this->model::getCajaAbierta();
        if ($caja) {

            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $caja
            ], Response::HTTP_OK);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => false,
            'data' => null
        ], Response::HTTP_OK);
    }

    public function getEstadisticas(Request $request)
    {
        $fechaInicio=$request->fecha_inicio;
        $fechaFin=$request->fecha_final;
        $rol=$request->rol;
        $user_id=$request->user_id;
        $caja = $this->model::getCajaAbierta();

        if($caja){
            $totalVentas=Venta::getTotalByDate($fechaInicio, $fechaFin, $caja->id);
            $pagos=Venta::getTotalByTipoPagoAndDate($fechaInicio, $fechaFin, $caja->id);
            $formaVenta=Venta::getTotalByFormaPago($caja->id);

            $efectivo=0;
            $contado=0;
            $credito=0;
            foreach($pagos as $pago){
                if($pago->nombre=='EFECTIVO'){
                    $efectivo=$pago->total;
                }else{
                    $credito+=$pago->total;
                }
            }

            foreach($formaVenta as $forma){
                if($forma->nombre=='Credito'){
                    $credito=$forma->total;
                }else{
                    $contado+=$forma->total;
                }
            }
            $totalProductos=Producto::getProductosStockMinimo();

            $data=[
                'caja'=>$caja,
                'caja_id'=>$caja->id,
                'totalVentas'=>$totalVentas,
                'efectivo'=>$efectivo,
                'contado'=>$contado,
                'credito'=>$credito,
                'totalProductos'=>$totalProductos,
            ];
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $data
            ], Response::HTTP_OK);
        }else{
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function getReporteHistorico(Request $request)
    {
        // Validación de datos
        $data = $request->only('fechaInicio', 'fechaFinal');
        $validator = Validator::make($data, [
            'fechaInicio' => 'required',
            'fechaFinal' => 'required',
        ]);
        $fecha_inicio = \Carbon\Carbon::parse($request->fechaInicio)->format('Y-m-d');
        $fecha_final = \Carbon\Carbon::parse($request->fechaFinal)->format('Y-m-d');

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }        // Buscamos la mesa
        $data=[];
        $data = $this->model::getByDateRange($fecha_inicio, $fecha_final);

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data'=>$data,
        ], Response::HTTP_OK);
    }

    public function getReporteHistoricoCajaMenor(Request $request)
    {
        // Validación de datos
        $data = $request->only('fechaInicio', 'fechaFinal');
        $validator = Validator::make($data, [
            'fechaInicio' => 'required',
            'fechaFinal' => 'required',
        ]);
        $fecha_inicio = \Carbon\Carbon::parse($request->fechaInicio)->format('Y-m-d');
        $fecha_final = \Carbon\Carbon::parse($request->fechaFinal)->format('Y-m-d');

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }        // Buscamos la mesa
        $data=[];
        $data = $this->model::getByDateRangeCajaMenor($fecha_inicio, $fecha_final);

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data'=>$data,
        ], Response::HTTP_OK);
    }




}
