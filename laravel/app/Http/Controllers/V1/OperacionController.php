<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Operacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class OperacionController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = Operacion::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todos los proveedores
        $operaciones = $this->model::get();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $operaciones
        ], Response::HTTP_OK);
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
        $data = $request->only('tipo_operacion_id', 'user_id', 'numero', 'fecha');
        $validator = Validator::make($data, [
            'tipo_operacion_id' => 'required',
            'user_id' => 'required',
            'numero' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Creamos el operacion en la BD
        $operacion = $this->model::create([
            'tipo_operacion_id' => ($request->tipo_operacion_id),
            'user_id' => $request->user_id,
            'numero' => $request->numero,
            'fecha' => Carbon::now(),
        ]);

        // Respuesta en caso de que todo vaya bien
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Operación Registrada Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Buscamos el proveedor
        $operacion = $this->model::find($id);

        // Si el operacion no existe devolvemos error no encontrado
        if (!$operacion) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Operación no encontrado'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $operacion
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validación de datos
        $data = $request->only('tipo_operacion_id', 'user_id', 'numero', 'fecha');
        $validator = Validator::make($data, [
            'tipo_operacion_id' => 'required',
            'user_id' => 'required',
            'numero' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el operacion
        $operacion = $this->model::findOrFail($id);

        // Actualizamos el operacion.
        $operacion->update([
            'tipo_operacion_id' => $request->tipo_operacion_id,
            'user_id' => $request->user_id,
            'numero' => $request->numero,
            'fecha' => $request->fecha,
        ]);

        // Respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Operación Actualizada Exitosamente',
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
        // Buscamos el operacion
        $operacion = $this->model::findOrFail($id);

        // Eliminamos el operacion
        $operacion->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Operación Eliminada Exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un operacion (Activo/Inactivo)
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

        // Buscamos el operacion
        $operacion = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $operacion->estado = ($operacion->estado == 1) ? 2 : 1;
        $operacion->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado de la Operación Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar todos los operaciones activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function activos()
    {
        // Listamos todos los registros activos
        $operaciones = $this->model::where('estado', 1)->get();
        if ($operaciones) {
            return response()->json([
                'code' => 200,
                'data' => $operaciones
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function getOperacionesDia(Request $request)
    {
        // Validación de datos
        $data = $request->only('fecha');
        $validator = Validator::make($data, [
            'fecha' => 'required'
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $fecha = Carbon::parse($request->fecha)->format('Y-m-d');
        // Buscamos el operacion
        $operacion = $this->model::getOperacionesDia($fecha);

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $operacion
        ], Response::HTTP_OK);
    }
}
