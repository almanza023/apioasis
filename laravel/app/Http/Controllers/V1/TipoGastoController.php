<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\TipoGasto;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class TipoGastoController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = TipoGasto::class;
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
        // Listamos todos los tipos de gasto
        $objeto = $this->model::get();
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
        $data = $request->only('nombre', 'descripcion', 'periodicidad');
        $validator = Validator::make($data, [
            'nombre' => 'required|max:200|string',
            'periodicidad' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Creamos el tipo de gasto en la BD
        $objeto = $this->model::create([
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
            'periodicidad' => $request->periodicidad,
        ]);

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Tipo de Gasto Creado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TipoGasto  $tipoGasto
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Buscamos el tipo de gasto
        $objeto = $this->model::find($id);

        // Si el tipo de gasto no existe devolvemos error no encontrado
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
     * @param  \App\Models\TipoGasto  $tipoGasto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validación de datos
        $data = $request->only('nombre', 'descripcion', 'periodicidad');
        $validator = Validator::make($data, [
            'nombre' => 'required|max:200|string',
            'periodicidad' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el tipo de gasto
        $objeto = $this->model::findOrFail($id);

        // Actualizamos el tipo de gasto.
        $objeto->update([
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
            'periodicidad' => $request->periodicidad,
        ]);

        // Devolvemos los datos actualizados.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Tipo de Gasto Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TipoGasto  $tipoGasto
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Buscamos el tipo de gasto
        $objeto = $this->model::findOrFail($id);

        // Eliminamos el tipo de gasto
        $objeto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Tipo de Gasto Eliminado Exitosamente'
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

        // Buscamos el tipo de gasto
        $objeto = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $objeto->estado = ($objeto->estado == 1) ? 2 : 1;
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
}
