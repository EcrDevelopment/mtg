<?php

namespace App\Http\Livewire\Reportes;

use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores, $totalPrecio, $cantidades;

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
            ->get();
        
        $cantidades = $certificaciones->groupBy(['nombre', 'tiposervicio'])->map(function ($items) {
            return [
                'cantidad' => $items->count(),
            ];
        });

        $totalPrecio = $certificaciones->sum('precio'); // Calcular el total de la columna "precio"
        //$certificaciones->totalPrecio = $totalPrecio;

        $this->resultados = $certificaciones;
        $this->cantidades = $cantidades;
        $this->totalPrecio = $totalPrecio;
        $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);
    }
}
