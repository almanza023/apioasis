<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class EstadoPedido extends Model
{
    protected $table = 'estados_pedidos';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function getAll(){
        return EstadoPedido::get();
    }

    public static function active(){
        return EstadoPedido::where('estado', 1)->get();
    }

}
