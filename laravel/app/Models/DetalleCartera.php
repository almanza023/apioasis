<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetalleCartera extends Model
{
    protected $table = 'detalles_cartera';

    protected $fillable = [
        'cartera_id',
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
        return $this->belongsTo(Cartera::class, foreignKey: 'cartera_id');
    }














}
