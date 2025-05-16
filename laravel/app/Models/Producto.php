<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'proveedor_id',
        'ubicacion_id',
        'nombre',
        'descripcion',
        'presentacion',
        'codigo',
        'precio',
        'precio_compra',
        'stock_actual',
        'stock_minimo',
        'laboratorio',
        'lote',
        'fecha_vencimiento',
        'porcentajegan',
        'ganancia',
        'estado'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, foreignKey: 'proveedor_id');
    }

    public static function getAll(){

        return self::with(['categoria', 'proveedor', 'bodegas'])->get()->map(function ($producto) {
            $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento);
            $fechaActual = Carbon::now();
            $diferencia = $fechaVencimiento->diff($fechaActual);

            if ($diferencia->days > 6 * 30) {
                $producto->etiqueta_vencimiento = 'Verde';
            } elseif ($diferencia->days > 3 * 30) {
                $producto->etiqueta_vencimiento = 'Amarillo';
            } else {
                $producto->etiqueta_vencimiento = 'Rojo';
            }

            return $producto;
        });
    }

    public static function getActivos(){
        return self::with(['categoria', 'proveedor'])->where('estado',1)->get()->map(function ($producto) {
            $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento);
            $fechaActual = Carbon::now();
            $diferencia = $fechaVencimiento->diff($fechaActual);

            if ($diferencia->days > 6 * 30) {
                $producto->etiqueta_vencimiento = 'Verde';
            } elseif ($diferencia->days > 3 * 30) {
                $producto->etiqueta_vencimiento = 'Amarillo';
            } else {
                $producto->etiqueta_vencimiento = 'Rojo';
            }

            return $producto;
        });
    }

    public static function getProductosStockMinimo()
    {
        return self::with('categoria')
            ->whereRaw('stock_actual <= stock_minimo')
            ->where('estado', 1)
            ->count();
    }

    public function bodegas()
    {
        return $this->hasMany(ProductoBodega::class, 'producto_id', 'id')
            ->with('bodega');
    }

    public static function getProductosProximosAVencer($diasLimite )
    {
        return self::with(['categoria'])
            ->where('estado', 1)
            ->whereDate('fecha_vencimiento', '<=', Carbon::now()->addDays($diasLimite))
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(function ($producto) {
                $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento);
                $diasRestantes = Carbon::now()->diffInDays($fechaVencimiento, false);
                $producto->dias_restantes = $diasRestantes;
                return $producto;
            })
            ->filter(function ($producto) use ($diasLimite) {
                return $producto->dias_restantes <= $diasLimite;
            });


    }

    public static function getProductosPorCategoria()
    {
        return self::where('estado', 1)
            ->orderBy('stock_actual', 'asc')
            ->get();
    }










}
