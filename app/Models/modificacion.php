<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class modificacion extends Model
{
    use HasFactory;
    protected $table="modificacion";

    public $fillable=[
        "id",
        "direccion",
        "chasis",
        "carroceria",
        "potencia",
        "rodante",
        "rectificacion",
    ];
}
