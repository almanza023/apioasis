<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Venta extends Model
{
    protected $table = 'ventas';
    protected $fillable = [
        'cliente_id',
        'user_id',
        'caja_id',
        'especial',
        'forma_venta',
        'fecha',
        'total',
        'cantidad',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagos()
    {
        return $this->hasMany(VentaTipoPago::class, foreignKey: 'venta_id');
    }

    public static function getAll()
    {
        return self::with(['detalles.producto', 'user', 'pagos.tipopago'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getTotalByDate($startDate, $endDate, $caja=null)
    {
        $query = self::where('estado', 1);
        if ($caja != null) {
            $query->where('caja_id', $caja);
        } else {
            $query->whereBetween('fecha', [$startDate, $endDate]);
        }
        return $query->sum('total');
    }

    public static function getVentasByDate($startDate, $endDate, $caja_id=null)
    {
        $query = self::with(['detalles.producto', 'user', 'pagos.tipopago'])
            ->where('estado', 1);
        if ($caja_id != null) {
            $query->where('caja_id', $caja_id);
        } else {
            $query->whereBetween('fecha', [$startDate, $endDate]);
        }
        $query->orderByDesc('id');
        return $query->where('estado', 1)->get();
    }

    public static function getTotalByTipoPagoAndDate($startDate, $endDate, $caja_id=null)
    {
        $query = VentaTipoPago::selectRaw('tipo_pagos.nombre, SUM(ventas_tipo_pagos.valor) as total')
            ->join('ventas', 'ventas.id', '=', 'ventas_tipo_pagos.venta_id')
            ->join('tipo_pagos', 'tipo_pagos.id', '=', 'ventas_tipo_pagos.tipopago_id')
                      ->where('ventas.estado', 1);

        if ($caja_id != null) {
            $query->where('ventas.caja_id', $caja_id);
        }else{
            $query->whereBetween('ventas.fecha', [$startDate, $endDate]);
        }

        return $query->groupBy('tipo_pagos.nombre')->get();
    }


    public static function getFilter($fecha_inicio = null, $fecha_fin = null, $estado, $rol, $cliente_id)
    {
        $query = self::with(['detalles.producto', 'user', 'pagos.tipopago', 'cliente']);
        if ($fecha_inicio !== null && $fecha_fin !== null) {
            $query->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
        }
        if($estado!=-1){
            $query->where('estado', $estado);
        }
        if($cliente_id!=-1){
            $query->where('cliente_id', $cliente_id);
        }
        return $query->orderByDesc('id')->get();
    }




}
