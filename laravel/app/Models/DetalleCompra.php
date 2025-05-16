<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetalleCompra extends Model
{
    protected $table = 'detalles_compras';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'precio',
        'precio_venta',
        'cantidad',
        'subtotal',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, foreignKey: 'compra_id');
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public static function getDetalleByCompra($compraId){
        return self::select('producto_id',
        DB::raw('SUM(cantidad) as total_cantidad'),
        DB::raw('SUM(subtotal) as total_subtotal'),
        'precio', 'id', 'precio_venta')
            ->where('compra_id', $compraId)
            ->groupBy('producto_id', 'precio')
            ->with('producto:id,nombre,precio') // Asegúrate de que el modelo Producto tenga los campos 'id', 'nombre' y 'precio'
            ->get();
        }








}
