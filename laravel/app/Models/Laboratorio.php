<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratorio extends Model
{
    protected $table = 'laboratorios';

    protected $fillable = [
        'nombre',
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

    public static function agregar($nombre){
        // Verificar si el laboratorio ya existe
        $laboratorio = self::where('nombre', strtoupper($nombre))->first();
        if (!$laboratorio) {
            // Si no existe, crearlo
            return self::create([
                'nombre' => strtoupper($nombre),
                'estado' => 1
            ]);
        }
        return $laboratorio;
    }

}
