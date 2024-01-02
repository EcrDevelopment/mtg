<?php

namespace App\Http\Livewire\Reportes;

use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores;

    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
    ];

    public function mount()
    {
        $this->inspectores = ServiciosImportados::groupBy('certificador')->pluck('certificador');
        $this->talleres = ServiciosImportados::groupBy('taller')->pluck('taller');
    }

    public function render()
    {
        return view('livewire.reportes.reporte-calcular', [
            'tiposServicio' => TipoServicio::all(),
        ]);
    }

    public function calcularReporte()
    {
        $this->validate();

        $certificaciones = DB::table('certificacion')
            ->select(
                'certificacion.idTaller',
                'certificacion.idInspector',
                'certificacion.idVehiculo',
                'certificacion.idServicio',
                'certificacion.created_at',
                'certificacion.precio',
                'users.name as nombre',
                'taller.nombre as taller',
                'vehiculo.placa as placa',
                'tiposervicio.descripcion as tiposervicio'


            )
            ->join('users', 'certificacion.idInspector', '=', 'users.id')
            ->join('taller', 'certificacion.idTaller', '=', 'taller.id')
            ->join('vehiculo', 'certificacion.idVehiculo', '=', 'vehiculo.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')

            ->whereBetween('certificacion.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            //->orWhere('tiposervicio.id', 11)// Incluye el tipo de servicio "Chip por deterioro"
            ->orWhere(function ($query) {
                // Incluir registros asociados al "Chip por deterioro"
                $query->where('tiposervicio.id', 11) // Tipo de servicio "Chip por deterioro"
                    ->whereIn('certificacion.id', function ($subquery) {
                        $subquery->select('idCertificacion')
                            ->from('certificacion_expediente');
                    });
            })
            ->get();

        // Obtener chips consumidos y agregarlos a los resultados
        $chipsConsumidos = $this->obtenerChipsConsumidos();
        $certificaciones = $certificaciones->union($chipsConsumidos);
        
        $totalPrecio = $certificaciones->sum('precio'); // Calcular el total de la columna "precio"
        // Agregar el total a los resultados
        //$certificaciones->totalPrecio = $totalPrecio;

        $this->resultados = $certificaciones->all();
        $this->emit('resultadosCalculados', $this->resultados);
    }


    public function obtenerChipsConsumidos()
    {
        return DB::table('material')
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
                ['material.idUsuario', '=', auth()->id()], // Filtra por el usuario actualmente autenticado
            ])
            ->get();
    }
}
