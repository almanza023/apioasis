<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model=User::class;
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
        //Listamos todos los productos
        $objeto=$this->model::getAll();
       if($objeto){
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            'data' => $objeto
        ], Response::HTTP_OK);
       }else{
        return response()->json([
            'code'=>200,
            'isSuccess'=>false,
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
        //Validamos los datos
        $data = $request->only('nombre', 'username','password', 'rol');

        $validator = Validator::make($data, [
            'nombre' => 'required',
            'username' => 'required',
            'rol' => 'required',
            'password' => 'required|string:min:6',

        ]);

        //Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        //Creamos el producto en la BD
        $objeto = $this->model::create([
            'nombre' => strtoupper($request->nombre),
            'username' => $request->username,
            'rol' => $request->rol,
            'password' => bcrypt($request->password)
        ]);

        //Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            'message' => 'Usuario Creado Exitosamente',
            'data' => $objeto
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       //Bucamos el producto
       $objeto = $this->model::find($id);

       //Si el producto no existe devolvemos error no encontrado
       if (!$objeto) {
           return response()->json([
               'code'=>200,
               'isSuccess'=>false,
               'message' => 'Registro no encontrado en la base de datos.'
           ], 404);
       }

       //Si hay producto lo devolvemos
       return response()->json([
           'code'=>200,
           'isSuccess'=>true,
           'data' => $objeto
       ], Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //Validación de datos
        $data = $request->only('nombre', 'username','password', 'rol');

        $validator = Validator::make($data, [
            'nombre' => 'required',
            'username' => 'required',
            'rol' => 'required',
        ]);

        //Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        //Buscamos el producto
        $objeto = $this->model::findOrfail($id);
        if(!empty($request->password)){
            $objeto->update([
                'nombre' => strtoupper($request->nombre),
                'username' => $request->username,
                'rol' => $request->rol,
                'password' => bcrypt($request->password)
            ]);
        }else{
            $objeto->update([
                'nombre' => strtoupper($request->nombre),
                'username' => $request->username,
                'rol' => $request->rol,

            ]);
        }


        //Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            'message' => 'Registro Actualizado Exitosamente',
            'data' => $objeto
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //Buscamos el producto
        $objeto = $this->model::findOrfail($id);

        //Eliminamos el producto
        $objeto->delete();

        //Devolvemos la respuesta
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            'message' => 'Registro Eliminado'
        ], Response::HTTP_OK);
    }

    public function cambiarEstado(Request $request)
    {
        //Validación de datos
        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required'          ]);
        //Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $objeto = $this->model::findOrfail($request->id);
        if($objeto->estado==1){
            $objeto->estado=2;
            $objeto->save();
        }else{
            $objeto->estado=1;
            $objeto->save();
        }
        //Devolvemos los datos actualizados.
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            'message' => 'Estado Actualizado Extiosamente',
            'data' => $objeto
        ], Response::HTTP_OK);
    }

    public function activos()
    {
        //Listamos todos los registros activos
        $objeto=$this->model::get();
       if($objeto){
        return response()->json([
            'code'=>200,
            'data' => $objeto
        ], Response::HTTP_OK);
       }else{
        return response()->json([
            'code'=>200,
            'data' => []
        ], Response::HTTP_BAD_REQUEST);
       }

    }



}
