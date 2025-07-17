<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $fillable = [
        'cartera_id',
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

    public function cartera()
    {
        return $this->belongsTo(Cartera::class, 'cartera_id');
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
