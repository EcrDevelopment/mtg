<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desmontes extends Model
{
    protected $table="desmontes";

    protected $fillable=
    ['id',
    'placa',
    'idTaller',
    'idServicio',
    'idInspector',
    'estado',
    'precio',
    'pagado',    
    'created_at',
    'updated_at',        
    ];


    public function Taller(){
        return $this->belongsTo(Taller::class,'idTaller');
    }

    public function Inspector(){
        return $this->belongsTo(User::class,'idInspector');
    }

    public function Servicio(){
        return $this->belongsTo(Servicio::class,'idServicio');
    }
}
