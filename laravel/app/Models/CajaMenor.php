<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class CajaMenor extends Model
{
    protected $table = 'caja_menor';

    protected $fillable = [
        'user_id',
        'fecha',
        'entradas',
        'salidas',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Get the user that owns the AperturaCaja
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getAll()
    {
        return self::with(['user'])
            ->orderByDesc('id')
            ->get();
    }



    public static function getByDateRange($startDate, $endDate)
    {
        return self::with(['user'])
            ->whereBetween('fecha', [$startDate, $endDate])
            ->orderByDesc('id')
            ->get();
    }

    public static function getDisponibleCaja()
    {
        $totalVentas = self::where('estado', 1)->sum('entradas');
        $totalGastos = self::where('estado', 1)->sum('salidas');

        return $totalVentas - $totalGastos;
    }

}
