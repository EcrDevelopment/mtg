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

    /*public function calcularReporte2()
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
    }*/

    public function calcularReporte()
    {
        $this->validate();

        $resultadosCertificacion = DB::table('certificacion')
            ->select(
                'created_at as fecha', // Cambia 'fecha' por 'created_at'
                //'placa',
                'idServicio as tipoServicio',
                'idInspector as certificador',
                'idTaller as taller',
                'precio'
            )
            ->whereBetween('created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();

        $resultados = ServiciosImportados::select(
            'fecha',
            'placa',
            'tipoServicio',
            'certificador',
            'taller',
            'precio'
        )
            ->whereBetween('fecha', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();

        // Combinar resultados de ambas tablas
        $resultados = $resultadosCertificacion->merge($resultados);

        // Ordenar resultados por el campo 'taller'
        $resultados = $resultados->sortBy('taller');

        $serviciosPorInspector = $resultados->groupBy(['certificador']);

        $resultadosFinales = [];

        foreach ($serviciosPorInspector as $certificador => $servicios) {
            $totalAnuales = $servicios->where('tipoServicio', 2)->count();
            $totalConversiones = $servicios->where('tipoServicio', 1)->count();
            $totalDesmontes = $servicios->where('tipoServicio', 6)->count();
            $totalModificacion = $servicios->where('tipoServicio', 5)->count(); 
            $totalAnualGLP = $servicios->where('tipoServicio', 4)->count();
            $totalConversionGLP = $servicios->where('tipoServicio', 3)->count();
            $totalChipDeterioro = $servicios->where('tipoServicio', 11)->count();
            $totalChipActivacion = $servicios->where('tipoServicio', 7)->count();
            $totalDuplicadoGNV = $servicios->where('tipoServicio', 8)->count();
            $totalDuplicadoGLP = $servicios->where('tipoServicio', 9)->count();
            $totalConverChip = $servicios->where('tipoServicio', 10)->count();
            $totalPreConverGNV = $servicios->where('tipoServicio', 12)->count();
            $totalPrecio = $servicios->sum('precio');

            $resultadosFinales[] = [
                'certificador' => $certificador,
                'totalAnuales' => $totalAnuales,
                'totalConversiones' => $totalConversiones,
                'totalDesmontes' => $totalDesmontes,
                'totalModificacion' => $totalModificacion,
                'totalAnualGLP' => $totalAnualGLP,
                'totalConversionGLP' => $totalConversionGLP,
                'totalChipDeterioro' => $totalChipDeterioro,
                'totalChipActivacion' => $totalChipActivacion,
                'totalDuplicadoGNV' => $totalDuplicadoGNV,
                'totalDuplicadoGLP' => $totalDuplicadoGLP,
                'totalConverChip' => $totalConverChip,
                'totalPreConverGNV' => $totalPreConverGNV,
                'totalPrecio' => $totalPrecio,
                'detalles' => $servicios->toArray(),
            ];
        }

        $this->resultados = $resultadosFinales;
        $this->emit('resultadosCalculados', $this->resultados);
    }
}
