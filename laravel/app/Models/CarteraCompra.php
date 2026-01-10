<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CarteraCompra extends Model
{
    protected $table = 'cartera_compras';
    protected $fillable = [
        'proveedor_id',
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

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }


    public function detalles()
    {
        return $this->hasMany(DetalleCarteraCompra::class, 'cartera_compra_id');
    }

    public function pagos()
    {
        return $this->hasMany(PagoCompra::class, foreignKey: 'cartera_compra_id');
    }

    public static function getAll()
    {
        return self::with(['proveedor', 'detalles', 'pagos'])
            ->orderByDesc('id')
            ->get();
    }


public static function verificarCartera($proveedor_id, $total,  $compra_id)
{
    $cartera = self::where('proveedor_id', $proveedor_id)
        ->where('estado', 1)
        ->first();

    if (!$cartera) {
        // Crear nueva cartera si no existe
        $cartera = self::create([
            'proveedor_id' => $proveedor_id,
            'fecha' => now(),
            'total' => $total,
            'abonos' => 0,
            'saldo' => $total,
            'observaciones' => 'Creada por Compra N° '.$compra_id,
            'estado' => 1,
        ]);

        $cartera->detalles()->create([
            'cartera_compra_id' => $cartera->id,
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
            'observaciones' => 'Actualizada por Compra N° '.$compra_id,
        ]);

        $cartera->detalles()->create([
            'cartera_compra_id' => $cartera->id,
            'total' => $total,
            'saldo' => $nuevoSaldo,
            'abono' => $cartera->abonos,
            'fecha' => now(),
            'estado' => 1,
        ]);
    }

    return $cartera;
}

public static function filter($proveedor_id, $estado){
    $query = self::query();
    if ($proveedor_id !== null) {
       $query->where('proveedor_id', $proveedor_id);
    }
    if ($estado !== 0) {
         $query->where('estado', $estado);
    }
    return $query->with(['proveedor', 'detalles', 'pagos'])->get();
}

public static function descontarCartera($proveedor_id, $compra_id, $total){

    $cartera=self::where('proveedor_id', $proveedor_id)
    ->where('estado', 1)
    ->first();
    if($cartera){
        $cartera->update([
            'total' => $cartera->total - $total,
            'saldo' => $cartera->saldo - $total,
        ]);

        $cartera->detalles()->create([
            'cartera_compra_id' => $cartera->id,
            'total' => $total,
            'saldo' => $cartera->saldo - $total,
            'fecha' => now(),
            'observaciones' => 'Anulación de Compra N° '.$compra_id,
            'estado' => 1,
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
        $proveedor_id=$cartera->proveedor_id;
        $totalVentas = Compra::where('proveedor_id', $proveedor_id)
        ->where('forma_pago', 2)
        ->where('estado', 2)
        ->where('cartera_id', $cartera->id)
        ->sum('total');

        if($totalVentas==0){
            return $cartera;
        }

        if(!empty($cartera->saldoinicial)){
            $totalVentas += $cartera->saldoinicial;
        }

        $cartera->update([
            'total' => $totalVentas,
            'observaciones'=>'Actualizada por Consulta de Cartera Compra',
            'saldo' => $totalVentas - $cartera->abonos,
        ]);


    }
    return $cartera;
}
}
