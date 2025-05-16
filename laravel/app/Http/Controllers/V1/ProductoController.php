<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Laboratorio;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoBodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = Producto::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todos los productos
        $productos = $this->model::getAll();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $productos
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
        $data = $request->only(
            'categoria_id',
            'user_id',
            'proveedor_id',
            'ubicacion_id',
            'codigo',
            'nombre',
            'descripcion',
            'lote',
            'laboratorio',
            'precio',
            'stock_actual',
            'fecha_vencimiento',
            'precio_compra',
            'detalles'
        );
        $validator = Validator::make($data, [
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'nombre' => 'required|max:200|string',
            //'precio' => 'required|numeric',
            'user_id' => 'required',
            'stock_actual' => 'required',
            //'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validación de imagen
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $inventario = false;
        DB::transaction(function () use ($request) {
            // Creamos el producto en la BD
            $producto = $this->model::create([
                'categoria_id' => $request->categoria_id,
                'proveedor_id' => $request->proveedor_id,
                'ubicacion_id' => $request->ubicacion_id,
                'nombre' => strtoupper($request->nombre),
                'laboratorio' => strtoupper($request->laboratorio),
                'descripcion' => $request->descripcion,
                'codigo' => $request->codigo,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'precio' => $request->precio,
                'precio_compra' => $request->precio_compra,
                'stock_actual' => $request->stock_actual,
                'ganancia' => $request->precio - $request->precio_compra,
                'porcentajegan' => $request->precio_compra == 0 ? 0 : round(($request->precio - $request->precio_compra) / $request->precio_compra, 2),
            ]);
            $productoId = $producto->id;
            $cantidad = $request->stock_actual;
            $user_id = $request->user_id;
            $precioVenta = $request->precio;
            $descripcion = "ENTRADA INICIAL";
            $tipoMovimiento = 1; // ENTRADA
            MovimientoInventario::create([
                'producto_id' => $productoId,
                'user_id' => $user_id,
                'tipo' => $tipoMovimiento,
                'cantidad' => $cantidad,
                'precio_venta' => $precioVenta,
                'precio_compra' => $request->precio_compra,
                'saldo' => $cantidad,
                'fecha' => now(),
                'descripcion' => $descripcion
            ]);


            foreach ($request->detalles as $item) {
                ProductoBodega::create([
                    'producto_id' => $productoId,
                    'bodega_id' => $item['bodega_id'],
                    'cantidad' => $item['cantidad'],
                    'fecha' => now()
                ]);
                $descripcion = "ENTRADA A BODEGA " . $item['bodega_id'];
                $cantidad = $item['cantidad'];
                MovimientoInventario::create([
                    'producto_id' => $productoId,
                    'user_id' => $user_id,
                    'tipo' => $tipoMovimiento,
                    'cantidad' => $cantidad,
                    'precio_venta' => $precioVenta,
                    'saldo' => $cantidad,
                    'fecha' => now(),
                    'descripcion' => $descripcion
                ]);
            }
        });

        // Respuesta en caso de que todo vaya bien

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Creado Exitosamente',
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
        // Buscamos el producto
        $producto = $this->model::find($id);

        // Si el producto no existe devolvemos error no encontrado
        if (!$producto) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $producto
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
        // Validamos los datos
        $data = $request->only(
            'categoria_id',
            'user_id',
            'proveedor_id',
            'ubicacion_id',
            'codigo',
            'nombre',
            'descripcion',
            'lote',
            'laboratorio',
            'precio',
            'stock_actual',
            'fecha_vencimiento',
            'precio_compra'
        );
        $validator = Validator::make($data, [
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'nombre' => 'required|max:200|string',
            'precio' => 'required|numeric',
            'stock_actual' => 'required|numeric',
            'user_id' => 'required',

        ]);
        // Buscamos el producto
        $producto = $this->model::findOrFail($id);
        $stoctkParcial = 0;

        // Actualizamos el producto.
        if ($request->stock_actual != $producto->stock_actual) {
            $stoctkParcial = $producto->stock_actual;
            DB::transaction(function () use ($producto, $request, $stoctkParcial) {
                $producto->update([
                    'categoria_id' => $request->categoria_id,
                    'proveedor_id' => $request->proveedor_id,
                    'ubicacion_id' => $request->ubicacion_id,
                    'nombre' => strtoupper($request->nombre),
                    'descripcion' => $request->descripcion,
                    'codigo' => $request->codigo,
                    'lote' => $request->lote,
                    'fecha_vencimiento' => $request->fecha_vencimiento,
                    'precio' => $request->precio,
                    'precio_compra' => $request->precio_compra,
                    'stock_actual' => $request->stock_actual,
                    'ganancia' => $request->precio - $request->precio_compra,
                    'porcentajegan' => $request->precio_compra == 0 ? 0 : round(($request->precio - $request->precio_compra) / $request->precio_compra, 2),
                ]);
                $user_id = $request->user_id;
                $descripcion = "ACTUALIZACION STOCK";
                $tipoMovimiento = 1; // ENTRADA
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'user_id' => $user_id,
                    'tipo' => $tipoMovimiento,
                    'cantidad' => $request->stock_actual,
                    'precio_venta' => $producto->precio,
                    'precio_compra' => $producto->precio_compra,
                    'saldo' => $stoctkParcial,
                    'fecha' => now(),
                    'descripcion' => $descripcion
                ]);

                //Actualizar en Bodega de Almacen Principal
                ProductoBodega::updateOrCreate(
                    ['producto_id' => $producto->id, 'bodega_id' => 5],
                    [
                        'cantidad' => $request->stock_actual,
                        'fecha' => now()
                    ]
                );
            });

            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Producto y Stock Actualizado Exitosamente',
            ], Response::HTTP_OK);
        } else {
            $producto->update([
                'categoria_id' => $request->categoria_id,
                'proveedor_id' => $request->proveedor_id,
                'ubicacion_id' => $request->ubicacion_id,
                'nombre' => strtoupper($request->nombre),
                'descripcion' => $request->descripcion,
                'codigo' => $request->codigo,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'precio' => $request->precio,
                'precio_compra' => $request->precio_compra,
                'ganancia' => $request->precio - $request->precio_compra,
                'porcentajegan' => $request->precio_compra == 0 ? 0 : round(($request->precio - $request->precio_compra) / $request->precio_compra, 2),
            ]);

            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Producto Actualizado Exitosamente',
            ], Response::HTTP_OK);
        }



        // Respuesta

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Buscamos el producto
        $producto = $this->model::findOrFail($id);

        // Eliminamos el producto
        $producto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Eliminado Exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un producto (Activo/Inactivo)
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

        // Buscamos el producto
        $producto = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $producto->estado = ($producto->estado == 1) ? 2 : 1;
        $producto->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado del Producto Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar todos los productos activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function activos()
    {
        // Listamos todos los registros activos
        $productos = $this->model::getActivos();
        if ($productos) {
            return response()->json([
                'code' => 200,
                'data' => $productos
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function movimientosInventario(Request $request)
    {
        // Validación de datos
        $data = $request->only('producto_id');
        $validator = Validator::make($data, [
            'producto_id' => 'required'
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el producto
        $data = MovimientoInventario::getMovimientosPorProducto($request->producto_id);
        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => '',
            'data' => $data
        ], Response::HTTP_OK);
    }


    public function storeLaboratorio(Request $request)
    {
        // Validación de datos
        $data = $request->only('nombre');
        $validator = Validator::make($data, [
            'nombre' => 'required'
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Creamos el laboratorio en la BD
        $laboratorio = new Laboratorio();
        $laboratorio->nombre = strtoupper($request->nombre);
        $laboratorio->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Laboratorio Creado Exitosamente',
        ], Response::HTTP_OK);
    }


    public function getLaboratorios()
    {
        $laboratorios = Laboratorio::active();
        return response()->json([
            'code' => 200,
            'data' => $laboratorios
        ], Response::HTTP_OK);
    }

    public function storeProductoBodega(Request $request)
    {
        //Validamos los datos
        $data = $request->only('producto_id',  'user_id', 'detalles');
        $validator = Validator::make($data, [
            'producto_id' => 'required',
            'user_id' => 'required',
            'detalles' => 'required',
        ]);

        //Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }



        foreach ($request->detalles as $item) {
            $productoId = $request->producto_id;
            $tipoMovimiento = 1;
            $precioVenta = 0;
            $user_id = $request->user_id;

            DB::beginTransaction();
            try {
                foreach ($request->detalles as $item) {
                    $productoId = $request->producto_id;
                    $tipoMovimiento = 1;
                    $precioVenta = 0;
                    $user_id = $request->user_id;

                    //Creamos el producto en la BD
                    $objeto = ProductoBodega::where('producto_id', $productoId)->where('bodega_id', $item['bodega_id'])->update(
                        [
                            'cantidad' => $item['cantidad'],
                            'fecha' => now()
                        ]
                    );

                    $descripcion = "TRASLADO DE BODEGA " . $item['bodega_id'];
                    $cantidad = $item['cantidad'];
                    MovimientoInventario::create([
                        'producto_id' => $productoId,
                        'user_id' => $user_id,
                        'tipo' => $tipoMovimiento,
                        'cantidad' => $cantidad,
                        'precio_venta' => $precioVenta,
                        'saldo' => $cantidad,
                        'fecha' => now(),
                        'descripcion' => $descripcion
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'code' => 500,
                    'isSuccess' => false,
                    'message' => 'Error al trasladar el producto a la bodega',
                    'error' => $e->getMessage()
                ], 500);
            }
            //Respuesta en caso de que todo vaya bien.
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Producto Trasladado a Bodega Exitosamente',
            ], Response::HTTP_OK);
        }
    }


    public function getProximosAVencer(Request $request)
    {
        $dias = $request->dias;
        $productos = Producto::getProductosProximosAVencer($dias);
        return response()->json([
            'code' => 200,
            'data' => $productos
        ], Response::HTTP_OK);
    }

    public function getProductosInventario()
    {
        // Listamos todos los registros activos
        $productos = $this->model::getProductosPorCategoria();
        if ($productos) {
            return response()->json([
                'code' => 200,
                'data' => $productos
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function storeImport(Request $request)
    {
        // Validamos que recibimos un array de productos
        if (!$request->has('productos') || !is_array($request->productos)) {
            return response()->json(['error' => 'Debe proporcionar un array de productos'], 400);
        }

        // Inicializamos contador de productos procesados
        $procesados = 0;
        $errores = [];

        // Procesamos cada producto del array
        DB::beginTransaction();
        try {
            foreach ($request->productos as $productoData) {
                // Verificamos datos mínimos requeridos
                if (empty($productoData['nombre']) || empty($productoData['cantidad'])
                || empty($productoData['precioVenta'])) {
                    $errores[] = "Producto sin nombre o cantidad: " . $productoData['nombre'];
                    continue;
                }

// Verificar si ya existe un producto con la misma descripción
                $presentacion = strtoupper($productoData['nombre']) . ' ' . ($productoData['presentacion'] ?? '');
                $productoExistente = $this->model::where('descripcion', $presentacion)->first();
                if ($productoExistente) {
                    $errores[] = "Ya existe un producto con la descripción: {$presentacion}";
                    continue;
                }


                $nombreLab=strtoupper($productoData['laboratorio']);
                $laboratorio=Laboratorio::agregar($nombreLab);
                // Creamos el producto en la BD
                $producto = $this->model::create([
                    'user_id' => $request->user_id,
                    'categoria_id' => 1,
                    'proveedor_id' => 1,
                    'ubicacion_id' => 42,
                    'nombre' => strtoupper($productoData['nombre']),
                    'laboratorio' => strtoupper($productoData['laboratorio'] ?? ''),
                    'descripcion' => $presentacion,
                    'codigo' => $productoData['codigoBarras'] ?? null,
                    'lote' => $productoData['lote'] ?? null,
                    'fecha_vencimiento' => !empty($productoData['fechaVencimiento']) ? \Carbon\Carbon::parse($productoData['fechaVencimiento'])->format('Y-m-d') : null,
                    'precio' => $productoData['precioVenta'] ?? 0,
                    'precio_compra' => $request->precio_compra ?? 0,
                    'stock_actual' => $productoData['cantidad'] ?? 0,
                    'ganancia' => 0,
                    'porcentajegan' => 0,
                ]);

                // Registramos movimiento de inventario si hay stock
                if (($productoData['cantidad'] ?? 0) > 0) {
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'user_id' => $request->user_id,
                        'tipo' => 1, // ENTRADA
                        'cantidad' => $productoData['cantidad'],
                        'precio_venta' => $productoData['precioVenta'] ?? 0,
                        'saldo' => $productoData['cantidad'],
                        'fecha' => now(),
                        'descripcion' => "IMPORTACIÓN MASIVA"
                    ]);
                }

                    $bodega_id=5;
                    ProductoBodega::create([
                        'producto_id' => $producto->id,
                        'bodega_id' => $bodega_id,
                        'cantidad' => $productoData['cantidad'],
                        'fecha' => now()
                    ]);
                    $descripcion = "ENTRADA A BODEGA " . $bodega_id;
                    $cantidad = $productoData['cantidad'];
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'user_id' => $request->user_id,
                        'tipo' => 1,
                        'cantidad' => $cantidad,
                        'precio_venta' => $productoData['precioVenta'] ?? 0,
                        'saldo' => $cantidad,
                        'fecha' => now(),
                        'descripcion' => $descripcion
                    ]);


                $procesados++;
            }

            DB::commit();

            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => "Importación completada. Productos procesados: $procesados",
                'errores' => $errores
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'isSuccess' => false,
                'message' => 'Error al importar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
