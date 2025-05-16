<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'numerodocumento',
        'telefono',
        'direccion',
        'ciudad',
        'nombrenegocio',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get(){
        return self::all();
    }

    public static function active(){
        return self::where('estado', 1)->get();
    }
}
