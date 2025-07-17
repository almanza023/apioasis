<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VentaTipoPago extends Model
{
    protected $table = 'ventas_tipo_pagos';
    protected $fillable = [
        'tipopago_id',
        'venta_id',
        'valor',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function tipopago()
    {
        return $this->belongsTo(TipoPago::class, 'tipopago_id');
    }

    public static function getPagosByVenta($ventaId){
        return self::select('ventas_tipo_pagos.*', 'tipo_pagos.nombre as tipo')
            ->join('tipo_pagos', 'ventas_tipo_pagos.tipopago_id', '=', 'tipo_pagos.id')
            ->where('ventas_tipo_pagos.venta_id', $ventaId)
            ->get();

    }




}
