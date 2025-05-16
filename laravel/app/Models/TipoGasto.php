<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoGasto extends Model
{
    protected $table = 'tipo_gastos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'periodicidad',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get(){
        return TipoGasto::all();
    }

    public static function active(){
        return TipoGasto::where('estado', 1)->get();
    }


    public function getPeriodicidadAttribute($value)
    {
        switch ($value) {
            case 1:
                return 'Diario';
                break;
            case 2:
                return 'Quincenal';
                break;
            case 3:
                return 'Mensual';
                break;
        }
    }

}
