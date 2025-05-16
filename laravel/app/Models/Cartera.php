<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cartera extends Model
{
    protected $table = 'cartera';
    protected $fillable = [
        'cliente_id',
        'fecha',
        'total',
        'abonos',
        'saldo',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }


    public function detalles()
    {
        return $this->hasMany(DetalleCartera::class, 'cartera_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, foreignKey: 'cartera_id');
    }

    public static function getAll()
    {
        return self::with(['cliente', 'detalles', 'pagos'])
            ->orderByDesc('id')
            ->get();
    }


public static function verificarCartera($cliente_id, $total,  $venta_id)
{
    $cartera = self::where('cliente_id', $cliente_id)
        ->where('estado', 1)
        ->first();

    if (!$cartera) {
        // Crear nueva cartera si no existe
        $cartera = self::create([
            'cliente_id' => $cliente_id,
            'fecha' => now(),
            'total' => $total,
            'abonos' => 0,
            'saldo' => $total,
            'observaciones' => 'Creada por Venta N° '.$venta_id,
            'estado' => 1,
        ]);

        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $cartera->total,
            'saldo' => $cartera->saldo,
            'abono' => $cartera->abonos,
            'fecha' => now(),
            'estado' => 1,
        ]);

    } else {
        // Actualizar cartera existente
        $nuevoTotal = $cartera->total + $total;
        $nuevoSaldo = $cartera->saldo + $total;

        $cartera->update([
            'total' => $nuevoTotal,
            'saldo' => $nuevoSaldo,
            'observaciones' => 'Actualizada por Venta N° '.$venta_id,
        ]);

        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $total,
            'saldo' => $nuevoSaldo,
            'abono' => $cartera->abonos,
            'fecha' => now(),
            'estado' => 1,
        ]);
    }

    return $cartera;
}

public static function filter($cliente_id, $estado){
    $query = self::query();
    if ($cliente_id !== null) {
       $query->where('cliente_id', $cliente_id);
    }
    if ($estado !== 0) {
         $query->where('estado', $estado);
    }
    return $query->with(['cliente', 'detalles', 'pagos'])->get();
}

public static function descontarCartera($cliente_id, $venta_id, $total){

    $cartera=self::where('cliente_id', $cliente_id)
    ->where('estado', 1)
    ->first();
    if($cartera){
        $cartera->update([
            'total' => $cartera->total - $total,
            'saldo' => $cartera->saldo - $total,
        ]);

        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $total,
            'saldo' => $cartera->saldo - $total,
            'fecha' => now(),
            'observaciones' => 'Anulación de Venta N° '.$venta_id,
            'estado' => 1,
        ]);
    }
    return $cartera;
}








}
