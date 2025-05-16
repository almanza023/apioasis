<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class EmpresaController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = Empresa::class;
    }

    public function index()
    {
        $empresas = $this->model::get();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $empresas
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $data = $request->only('nombre', 'nit', 'telefono', 'direccion', 'contacto');
        $validator = Validator::make($data, [
            'nombre' => 'required|max:200|string',
            'nit' => 'required|string|unique:empresas',
            'telefono' => 'required|string',
            'direccion' => 'required|string',
            'contacto' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $empresa = $this->model::create([
            'nombre' => strtoupper($request->nombre),
            'nit' => $request->nit,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'contacto' => $request->contacto,
        ]);

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Empresa Creada Exitosamente',
        ], Response::HTTP_OK);
    }

    public function show($id)
    {
        $empresa = $this->model::find($id);

        if (!$empresa) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Empresa no encontrada en la base de datos.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $empresa
        ], Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only('nombre', 'nit', 'telefono', 'direccion', 'contacto');
        $validator = Validator::make($data, [
            'nombre' => 'required|max:200|string',
            'nit' => 'required|string|unique:empresas,nit,'.$id,
            'telefono' => 'required|string',
            'direccion' => 'required|string',
            'contacto' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $empresa = $this->model::findOrFail($id);

        $empresa->update([
            'nombre' => strtoupper($request->nombre),
            'nit' => $request->nit,
            'telefono' => $request->telefono,
            'direccion' => strtoupper($request->direccion),
            'contacto' => strtoupper($request->contacto),
        ]);

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Empresa Actualizada Exitosamente',
        ], Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $empresa = $this->model::findOrFail($id);
        $empresa->delete();

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Empresa Eliminada Exitosamente'
        ], Response::HTTP_OK);
    }

    public function cambiarEstado(Request $request)
    {
        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $empresa = $this->model::findOrFail($request->id);
        $empresa->estado = $empresa->estado == 1 ? 2 : 1;
        $empresa->save();

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado de Empresa Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    public function activos()
    {
        $empresasActivas = $this->model::active()->get();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $empresasActivas
        ], Response::HTTP_OK);
    }
}
