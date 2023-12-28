<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ListadoChips extends Component
{
    public $chipsConsumidos;

    public function mount()
    {
        $this->obtenerChipsConsumidos();
    }

    public function render()
    {
        return view('livewire.listado-chips');
    }

    public function obtenerChipsConsumidos()
    {
        $this->chipsConsumidos = DB::table('material')
            ->select(
                'material.id',
                'material.idUsuario',
                'material.estado',
                'material.ubicacion',
                'material.grupo',
                'material.updated_at',
                'users.name as nombreInspector',
                //'tiposervicio.descripcion'
            )
            ->join('users', 'material.idUsuario', '=', 'users.id')
            ->where([
                ['material.estado', '=', 4], // Chips consumidos
                ['material.idTipoMaterial', '=', 2], // Tipo de material CHIP
                //['tiposervicio.id', '=', 11], // Id del servicio "Chip por deterioro"
                ['material.idUsuario', '=', auth()->id()], // Filtra por el usuario actualmente autenticado
            ])
            ->get();
           // dd($this->chipsConsumidos);
    } 
}
