<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetalleVenta extends Model
{
    protected $table = 'detalles_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'precio',
        'cantidad',
        'descuento',
        'devueltas',
        'subtotal',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function compra()
    {
        return $this->belongsTo(Venta::class, foreignKey: 'venta_id');
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public static function getDetalleByVenta($ventaId){
        return self::select('venta_id','producto_id', 'precio','descuento', 'id',
        DB::raw('SUM(cantidad) as total_cantidad'),
        DB::raw('SUM(subtotal) as total_subtotal'))
            ->where('venta_id', $ventaId)
            ->groupBy('producto_id')
            ->with('producto:id,nombre,precio')
            ->get();
        }












}
