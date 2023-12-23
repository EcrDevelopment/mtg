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
        // Implementa la lógica para obtener los chips consumidos por inspector
        $this->chipsConsumidos = DB::table('material')
            ->select(
                'material.id',
                //'material.descripcion',
                //'material.numSerie',
                'material.idUsuario',
                //'material.añoActivo',
                'material.estado',
                'material.ubicacion',
                'material.grupo',
                //'material.idTipoMaterial',
                'material.created_at',
                'material.updated_at',
                //'usuarios.nombre as nombreInspector' // Ajusta el nombre de la tabla según tu esquema
            )
            ->join('users', 'material.idUsuario', '=', 'idUsuario')
            ->where([
                ['material.estado', '=', 4], // Chips consumidos
                ['material.idTipoMaterial', '=', 2], // Tipo de material CHIP
            ])
            ->get();
    }
}
