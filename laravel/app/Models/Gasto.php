<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'tipogasto_id',
        'tipocaja',
        'fecha',
        'caja_id',
        'descripcion',
        'valortotal',
        'estado',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function getAll()
    {
        return self::with(['tipogasto'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getByDateRange($startDate, $endDate)
    {
        return self::with(['tipogasto'])
            ->whereBetween('fecha', [$startDate, $endDate])
            ->orderByDesc('id')
            ->get();
    }

    public function tipogasto()
    {
        return $this->belongsTo(TipoGasto::class, 'tipogasto_id');
    }

    public static function getTotalByDate($startDate, $endDate, $caja=null)
    {
        $query = self::where('estado', 1);
        if ($caja != null) {
            $query->where('caja_id', $caja);
        }else{
            $query->whereBetween('fecha', [$startDate, $endDate]);
        }
        return $query->sum('valortotal');
    }

    public static function getGastosByDate($startDate, $endDate, $caja_id=null)
    {
        $query = self::with(['tipogasto'])
            ->where('estado', 1);
        if ($caja_id == null) {
            $query->whereBetween('fecha', [$startDate, $endDate]);
        } else {
            $query->where('caja_id', $caja_id);
            $query->where('tipocaja', 1);
        }
        $query->orderByDesc('id');
        return $query->get();
    }







}
