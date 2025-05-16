<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetalleCarteraCompra extends Model
{
    protected $table = 'detalles_cartera_compras';

    protected $fillable = [
        'cartera_compra_id',
        'total',
        'saldo',
        'abono',
        'fecha',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cartera()
    {
        return $this->belongsTo(CarteraCompra::class, foreignKey: 'cartera_compra_id');
    }














}
