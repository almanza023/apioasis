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
        'saldoinicial',
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



public static function verificarCartera($cliente_id, $total,  $venta_id, $cambiar_cliente=false)
{
    $cartera = self::where('cliente_id', $cliente_id)
        ->where('estado', 1)
        ->first();

    if (!$cartera || $cambiar_cliente) {
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

public static function updateTotalByVenta($venta_id, $total)
{
    $cartera = self::whereHas('ventas', function($query) use ($venta_id) {
        $query->where('ventas.id', $venta_id);
    })->first();

    if ($cartera) {
        $nuevoTotal = $cartera->total - $total;
        $cartera->update([
            'total' => $nuevoTotal,
            'saldo' => $nuevoTotal,
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

public static function triggerVentas($cliente_id, $venta_id, $is_delete = false)
{

    $cartera = self::where('cliente_id', $cliente_id)
        ->where('estado', 1)
        ->first();
    $totalVentas = 0;
    if($cartera){
        $totalVentas = Venta::where('cliente_id', $cliente_id)
        ->where('forma_venta', 2)
        ->where('estado', 1)
        ->where('cartera_id', $cartera->id)
        ->sum('total');
    }else{
        $totalVentas = Venta::where('cliente_id', $cliente_id)
        ->where('forma_venta', 2)
        ->where('estado', 1)
        ->sum('total');
    }
    if ($cartera) {
        $saldoInicial=$cartera->saldoinicial;
        $nuevoTotal=0;
        $nuevoSaldo=0;
        if($saldoInicial){
            $nuevoTotal = $saldoInicial + $totalVentas ;
        }else{
            $nuevoTotal = $totalVentas;
        }
        $nuevoSaldo = $nuevoTotal - $cartera->abonos;

        $cartera->update([
            'total' => $nuevoTotal,
            'saldo' => $nuevoSaldo,
            'observaciones'=>'Actualizada por Ventas N° '.$venta_id,
        ]);

        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $nuevoTotal,
            'abono'=>$cartera->abonos,
            'saldo' => $nuevoSaldo,
            'fecha' => now(),
            'observaciones' => 'Ventas N° '.$venta_id,
            'estado' => 1,
        ]);
    } else {
        $cartera = self::create([
            'cliente_id' => $cliente_id,
            'total' => $totalVentas,
            'saldo' => $totalVentas,
            'fecha' => now(),
            'estado' => 1,
        ]);

        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $totalVentas,
            'saldo' => $totalVentas,
            'fecha' => now(),
            'observaciones' => 'Ventas N° '.$venta_id,
            'estado' => 1,
        ]);
    }

    if ($is_delete) {
        $cartera->update([
            'total' => $cartera->total - $totalVentas,
            'saldo' => $cartera->saldo - $totalVentas,
        ]);

        $cartera->detalles()->create([
            'cartera_id' => $cartera->id,
            'total' => $totalVentas,
            'saldo' => $cartera->saldo - $totalVentas,
            'fecha' => now(),
            'observaciones' => 'Anulación de Venta N° ' . $venta_id,
            'estado' => 1,
        ]);
    }
    //cerrar la cartera
    if( $cartera->saldo == 0){
        $cartera->update([
            'estado' => 2,
            'observaciones'=>'Cerrada por Ventas N° '.$venta_id,
        ]);
    }
    return $cartera;
}

public static function actualizarCartera($cartera_id) {
    $cartera=self::where('id',$cartera_id)
    ->where('estado',1)
    ->first();
    $totalVentas=0;
    if($cartera){
        $cliente_id=$cartera->cliente_id;
        $totalVentas = Venta::where('cliente_id', $cliente_id)
        ->where('forma_venta', 2)
        ->where('estado', 1)
        ->where('cartera_id', $cartera->id)
        ->sum('total');

        if(!empty($cartera->saldoinicial)){
            $totalVentas += $cartera->saldoinicial;
        }
        $cartera->update([
            'total' => $totalVentas,
            'observaciones'=>'Actualizada por Consulta de Cartera',
            'saldo' => $totalVentas - $cartera->abonos,
        ]);
    }
    return $cartera;
}
}
