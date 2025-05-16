<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = Cliente::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todos los clientes
        $clientes = $this->model::get();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $clientes
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
        $data = $request->only('nombre', 'numerodocumento', 'telefono', 'ciudad', 'direccion', 'nombrenegocio');
        $validator = Validator::make($data, [
            'nombre' => 'required|max:200|string',
            'numerodocumento' => 'required|max:20|unique:clientes',
            'telefono' => 'required',
            'direccion' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Creamos el cliente en la BD
        $cliente = $this->model::create([
            'nombre' => strtoupper($request->nombre),
            'numerodocumento' => $request->numerodocumento,
            'telefono' => $request->telefono,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'nombrenegocio' => $request->nombrenegocio,
        ]);

        // Respuesta en caso de que todo vaya bien
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cliente Creado Exitosamente',
            'data' => $cliente
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
        // Buscamos el cliente
        $cliente = $this->model::find($id);

        // Si el cliente no existe devolvemos error no encontrado
        if (!$cliente) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $cliente
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
        $data = $request->only('nombre', 'numerodocumento', 'telefono', 'ciudad', 'direccion', 'nombrenegocio');
        $validator = Validator::make($data, [
            'nombre' => 'required|max:200|string',
            'numerodocumento' => 'required|max:20|unique:clientes,numerodocumento,' . $id,
            'direccion' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el cliente
        $cliente = $this->model::findOrFail($id);

        // Actualizamos el cliente.
        $cliente->update([
            'nombre' => strtoupper($request->nombre),
            'numerodocumento' => $request->numerodocumento,
            'telefono' => $request->telefono,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'nombrenegocio' => $request->nombrenegocio,
        ]);

        // Respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cliente Actualizado Exitosamente',
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
        // Buscamos el cliente
        $cliente = $this->model::findOrFail($id);

        // Eliminamos el cliente
        $cliente->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Cliente Eliminado Exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un cliente (Activo/Inactivo)
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

        // Buscamos el cliente
        $cliente = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $cliente->estado = ($cliente->estado == 1) ? 2 : 1;
        $cliente->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado del Cliente Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar todos los clientes activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function activos()
    {
        // Listamos todos los registros activos
        $clientes = $this->model::where('estado', 1)->get();
        if ($clientes) {
            return response()->json([
                'code' => 200,
                'data' => $clientes
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }
}
