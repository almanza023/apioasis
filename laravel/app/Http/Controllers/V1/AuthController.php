<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\CargaAcademica;
use App\Models\DireccionGrado;
use JWTAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    //Función que utilizaremos para registrar al usuario
    public function register(Request $request)
    {
        //Indicamos que solo queremos recibir name, email y password de la request
        $data = $request->only('nombre', 'username', 'password', 'rol');

        //Realizamos las validaciones
        $validator = Validator::make($data, [
            'nombre' => 'required',
            'username' => 'required',
            'password' => 'required|string|min:6|max:50',
            'rol' => 'required',
        ]);

        //Devolvemos un error si fallan las validaciones
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        //Creamos el nuevo usuario
        $user = User::create([
            'nombre' => strtoupper($request->nombre),
            'username' => $request->username,
            'rol' => $request->tipo,
            'password' => bcrypt($request->password)
        ]);

        //Nos guardamos el usuario y la contraseña para realizar la petición de token a JWTAuth
        $credentials = $request->only('username', 'password');

        //Devolvemos la respuesta con el token del usuario
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            "message"=>"Usuario Creado Exitosamente",
            'name' => $user->name,
            'username' => $user->username,
            'acccess_token' => JWTAuth::attempt($credentials),

        ], Response::HTTP_OK);
    }

    //Funcion que utilizaremos para hacer login
    public function authenticate(Request $request)
    {
        //Indicamos que solo queremos recibir email y password de la request
        $credentials = $request->only('username', 'password');

        //Validaciones
        $validator = Validator::make($credentials, [
            'username' => 'required',
            'password' => 'required'
        ]);

        //Devolvemos un error de validación en caso de fallo en las verificaciones
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        //Intentamos hacer login
        try {
            if (!$token = JWTAuth::attempt($credentials)) {

                //Credenciales incorrectas.
                return response()->json([
                    'isSuccess'=>false,
                    'message' => 'Login failed',
                ], 401);

            }
        } catch (JWTException $e) {

            //Error chungo
            return response()->json([
                'isSuccess'=>false,
                'message' => 'Error',
            ], 500);

        }
        $user=Auth::user();
        //Devolvemos el token
        return response()->json([
            'code'=>200,
            'isSuccess'=>true,
            "message"=>"Exitoso",
            'data'=>[
            "nombre"=>$user->nombre,
            "user_id"=>$user->id,
            "username"=>$user->username,
            'access_token' => $token,
            'rol' => $user->rol,
            ]

        ]);

    }

    //Función que utilizaremos para eliminar el token y desconectar al usuario
    public function logout(Request $request)
    {
        //Validamos que se nos envie el token
        $validator = Validator::make($request->only('token'), [
            'token' => 'required'
        ]);

        //Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }


        try {
            //Si el token es valido eliminamos el token desconectando al usuario.
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'Usuario desconectado'
            ]);

        } catch (JWTException $exception) {

            //Error chungo

            return response()->json([
                'success' => false,
                'message' => 'Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

    //Función que utilizaremos para obtener los datos del usuario y validar si el token a expirado.
    public function getUser(Request $request)
    {
        //Validamos que la request tenga el token
        $this->validate($request, [
            'token' => 'required'
        ]);

        //Realizamos la autentificación
        $user = JWTAuth::authenticate($request->token);

        //Si no hay usuario es que el token no es valido o que ha expirado
        if(!$user)
            return response()->json([
                'message' => 'Invalid token / token expired',
            ], 401);

        //Devolvemos los datos del usuario si todo va bien.
        return response()->json(['user' => $user]);
    }

    public function cambiarClave(Request $request)
    {
        //Indicamos que solo queremos recibir name, email y password de la request
        $data = $request->only('nuevaclave', 'confirmacion', 'usuario_id');

        //Realizamos las validaciones
        $validator = Validator::make($data, [
            'nuevaclave' => 'required|string',
            'confirmacion' => 'required|string',
            'usuario_id' => 'required'
        ]);

        //Devolvemos un error si fallan las validaciones
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $user=User::find($request->usuario_id);
        $password=bcrypt($request->nuevaclave);
        $user->password=$password;
        $user->save();


        //Devolvemos la respuesta con el token del usuario
      if($user){
        return response()->json([
            'code'=>200,
            'message' => 'Contraseña Actualizada Exitosamente',

        ], Response::HTTP_OK);
      }else{
        return response()->json([
            'code'=>400,
            'message' => 'Error al actualizar Contraseña',

        ], Response::HTTP_OK);
      }
    }

    public function update(Request $request)
    {
        //Indicamos que solo queremos recibir name, email y password de la request
        $data = $request->only('nombre', 'clave', 'documento', 'usuario_id');

        //Realizamos las validaciones
        $validator = Validator::make($data, [
            'nombre' => 'required|string',
            'documento' => 'required',
            'usuario_id' => 'required'
        ]);

        //Devolvemos un error si fallan las validaciones
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $user=User::find($request->usuario_id);
        $user->name=$request->name;
        $user->documento=$request->documento;
        if(!empty($request->clave)){
            $user->password=bcrypt($request->clave);
        }
        $user->save();


        //Devolvemos la respuesta con el token del usuario
      if($user){
        return response()->json([
            'code'=>200,
            'message' => 'Datos Actualizado Exitosamente',

        ], Response::HTTP_OK);
      }else{
        return response()->json([
            'code'=>400,
            'message' => 'Error al actualizar Contraseña',

        ], Response::HTTP_OK);
      }
    }


}
