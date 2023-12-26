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
                //'material.descripcion',
                //'material.numSerie',
                'material.idUsuario',
                //'material.añoActivo',
                'material.estado',
                'material.ubicacion',
                'material.grupo',
                //'material.idTipoMaterial',
                'material.created_at',
                //'material.updated_at',
                'users.name as nombreInspector',
                //'tiposervicio.descripcion as servicioDescripcion'

            )
            ->join('users', 'material.idUsuario', '=', 'users.id')
            //->leftJoin('servicio', 'servicio.tipoServicio_idtipoServicio', '=', 'servicio.tipoServicio_idtipoServicio')
            //->leftJoin('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where([
                ['material.estado', '=', 4], // Chips consumidos
                ['material.idTipoMaterial', '=', 2], // Tipo de material CHIP
                ['material.idUsuario', '=', auth()->id()], // Filtra por el usuario actualmente autenticado
            ])
            ->get();
    }
}
