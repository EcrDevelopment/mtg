<?php

namespace App\Http\Livewire\Reportes;

use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
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
        $this->inspectores=ServiciosImportados::groupBy('certificador')->pluck('certificador');
        $this->talleres=ServiciosImportados::groupBy('taller')->pluck('taller');            
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

        $resultados = ServiciosImportados::select(
            'fecha',
            'placa',
            'tipoServicio',
            'certificador',
            'taller'
        )
            ->whereBetween('fecha', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();

            $serviciosPorInspector = $resultados->groupBy('certificador');

            $resultadosFinales = [];

            foreach ($serviciosPorInspector as $certificador => $servicios) {
                $totalAnuales = $servicios->where('tipoServicio', 2)->count();
                $totalConversiones = $servicios->where('tipoServicio', 1)->count();
                $totalDesmontes = $servicios->where('tipoServicio', 6)->count();
        
                $resultadosFinales[] = [
                    'certificador' => $certificador,
                    'totalAnuales' => $totalAnuales,
                    'totalConversiones' => $totalConversiones,
                    'totalDesmontes' => $totalDesmontes,
                    'detalles' => $servicios->toArray(), // Opcional: Si necesitas los detalles de cada servicio
                ];
            }

        $this->resultados = $resultadosFinales;
        $this->emit('resultadosCalculados', $this->resultados);
    }
}
