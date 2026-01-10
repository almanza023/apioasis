<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Compra extends Model
{
    protected $table = 'compras';
    protected $fillable = [
        'proveedor_id',
        'user_id',
        'bodega_id',
        'fecha',
        'total',
        'forma_pago',
        'cantidad',
        'cartera_id',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, foreignKey: 'bodega_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    public static function getAll()
    {
        return self::with(relations: ['proveedor', 'user', 'bodega'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getFilter($estado = null, $proveedor_id = null, $fecha_inicio = null, $fecha_fin = null)
    {
        $query = self::with(['proveedor', 'user', 'bodega']);

        if ($estado !== null && $estado != 0) {
            $query->where('estado', $estado);
        }

        if ($proveedor_id !== null) {
            $query->where('proveedor_id', $proveedor_id);
        }

        if ($fecha_inicio !== null && $fecha_fin !== null) {
            $query->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
        }

        return $query->orderByDesc('id')->get();
    }

    public static function getTotalByContado($caja_id)
    {
        $query = self::where('estado', 1)->where('forma_pago', 2);
        if ($caja_id != null) {
            $query->where('caja_id', $caja_id);
        }
        return $query->sum('total');
    }


}
