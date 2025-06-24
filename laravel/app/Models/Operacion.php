<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Operacion extends Model
{
    protected $table = 'operaciones';

    protected $fillable = [
        'tipo_operacion_id',
        'numero',
        'fecha',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get()
    {
        return Operacion::all();
    }

    public static function getOperacionesDia($fecha)
    {

        $operaciones = self::whereDate('fecha', $fecha)->get();
        $data=[];
        foreach ($operaciones as $operacion) {

            if($operacion->tipo_operacion_id == 1){
                $temp_data =Operacion::join('ventas', 'operaciones.numero', '=', 'ventas.id')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->join('tipo_operaciones', 'operaciones.tipo_operacion_id', '=', 'tipo_operaciones.id')
                ->leftJoin('ventas_tipo_pagos', function ($join) {
                    $join->on('ventas.id', '=', 'ventas_tipo_pagos.venta_id')
                        ->where('ventas.forma_venta', '<>', 2);
                })
                ->leftJoin('tipo_pagos', 'ventas_tipo_pagos.tipopago_id', '=', 'tipo_pagos.id')
                ->select('operaciones.*', 'ventas.total as valor', 'clientes.nombre as nombre', 'tipo_operaciones.nombre as tipo_operacion', 'operaciones.created_at as fecha_creacion',
                    DB::raw('CASE WHEN ventas.forma_venta = 2 THEN "CREDITO" ELSE tipo_pagos.nombre END as tipo_pago'))
                ->where('operaciones.id', $operacion->id)
                ->first();
                array_push($data, $temp_data);
            }else if($operacion->tipo_operacion_id == 2){
               $temp_data =Operacion::join('compras', 'operaciones.numero', '=', 'compras.id')
               ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
               ->join('tipo_operaciones', 'operaciones.tipo_operacion_id', '=', 'tipo_operaciones.id')
               ->select('operaciones.*', 'compras.total as valor', 'proveedores.nombre as nombre', 'tipo_operaciones.nombre as tipo_operacion', 'operaciones.created_at as fecha_creacion')
               ->where('operaciones.id', $operacion->id)
               ->first();
               array_push($data, $temp_data);
            }else if($operacion->tipo_operacion_id == 3){
                $temp_data =Operacion::join('pagos', 'operaciones.numero', '=', 'pagos.id')
                ->join('cartera', 'pagos.cartera_id', '=', 'cartera.id')
                ->join('clientes', 'clientes.id', '=', 'cartera.cliente_id')
                ->join('tipo_operaciones', 'operaciones.tipo_operacion_id', '=', 'tipo_operaciones.id')
                ->join('tipo_pagos', 'pagos.tipo_pago_id', '=', 'tipo_pagos.id')
                ->select('operaciones.*', 'pagos.valor as valor', 'clientes.nombre as nombre', 'tipo_operaciones.nombre as tipo_operacion', 'operaciones.created_at as fecha_creacion', 'tipo_pagos.nombre as tipo_pago')
                ->where('operaciones.id', $operacion->id)
                ->first();
                array_push($data, $temp_data);
             }
             else if($operacion->tipo_operacion_id == 4){
                $temp_data =Operacion::join('pagos_compras', 'operaciones.numero', '=', 'pagos_compras.id')
                ->join('cartera_compras', 'pagos_compras.cartera_compra_id', '=', 'cartera_compras.id')
                ->join('proveedores', 'proveedores.id', '=', 'cartera_compras.proveedor_id')
                ->join('tipo_operaciones', 'operaciones.tipo_operacion_id', '=', 'tipo_operaciones.id')
                ->join('tipo_pagos', 'pagos_compras.tipo_pago_id', '=', 'tipo_pagos.id')
                ->select('operaciones.*', 'pagos_compras.valor as valor', 'proveedores.nombre as nombre', 'tipo_operaciones.nombre as tipo_operacion', 'operaciones.created_at as fecha_creacion', 'tipo_pagos.nombre as tipo_pago')
                ->where('operaciones.id', $operacion->id)
                ->first();
                array_push($data, $temp_data);
             }
         }

        return $data;
    }

    /**
     * Get the user that owns the Operacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(TipoOperacion::class, 'tipo_operacion_id', 'id');
    }




}
