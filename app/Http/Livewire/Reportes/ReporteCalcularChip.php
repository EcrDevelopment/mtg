<?php

namespace App\Http\Livewire\Reportes;

use App\Models\Certificacion;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Taller;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReporteCalcularChip extends Component
{
    public $chipsConsumidos, $fechaInicio, $fechaFin;
    public $inspectores, $talleres ;

    public function mount()
    {
        $this->obtenerChipsConsumidos();
        //$this->inspectores = Certificacion::groupBy('idInspector')->pluck('idInspector');
        //$this->talleres = Certificacion::groupBy('idTaller')->pluck('idTaller');
        $this->inspectores = User::role(['inspector', 'supervisor'])->where('id', '!=', Auth::id())->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
    }

    public function render()
    {
        return view('livewire.reportes.reporte-calcular-chip');
    }

    public function obtenerChipsConsumidos()
    {
        // Obtener chips consumidos para todos los inspectores
        $this->chipsConsumidos = DB::table('material')
            ->select(
                'material.id',
                'material.idUsuario',
                'material.estado',
                'material.ubicacion',
                'material.grupo',
                'material.updated_at',
                'users.name as nombreInspector',
            )
            ->join('users', 'material.idUsuario', '=', 'users.id')
            ->where([
                ['material.estado', '=', 4], // Chips consumidos
                ['material.idTipoMaterial', '=', 2], // Tipo de material CHIP
            ])
            ->whereBetween('material.updated_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();
    }
}
