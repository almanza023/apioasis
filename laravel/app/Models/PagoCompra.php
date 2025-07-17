<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PagoCompra extends Model
{
    protected $table = 'pagos_compras';
    protected $fillable = [
        'cartera_compra_id',
        'tipo_pago_id',
        'caja_id',
        'fecha',
        'valor',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function carteraCompra()
    {
        return $this->belongsTo(CarteraCompra::class, 'cartera_compra_id');
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }

    public static function getTotalByDate($caja_id){
        return self::where('caja_id', $caja_id)
            ->where('tipo_pago_id', 1)
            ->sum('valor');
    }










}
