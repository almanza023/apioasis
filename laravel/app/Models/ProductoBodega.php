<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoBodega extends Model
{
    protected $table = 'productos_bodegas';

    protected $fillable = [
        'producto_id',
        'bodega_id',
        'cantidad',
        'fecha',
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


    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }


    public static function updateCantidadByProductoAndBodega($producto_id, $bodega_id, $cantidad, $tipo){
        $registro = self::where('producto_id', $producto_id)
            ->where('bodega_id', $bodega_id)
            ->first();
        if($tipo == 1){
            $registro->cantidad = $registro->cantidad - $cantidad;
        }else{
            $registro->cantidad = $registro->cantidad + $cantidad;
        }
        $registro->save();
    }


    public static function getCantidadByProductoId($producto_id){
        return self::select('productos_bodegas.cantidad', 'bodegas.nombre as bodega_nombre')
            ->join('bodegas', 'bodegas.id', '=', 'productos_bodegas.bodega_id')
            ->where('productos_bodegas.producto_id', $producto_id)
            ->get();
    }




}
