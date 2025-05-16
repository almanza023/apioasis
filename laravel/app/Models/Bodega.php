<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $table = 'bodegas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'telefono',
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
