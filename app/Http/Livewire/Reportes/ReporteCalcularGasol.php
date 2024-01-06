<?php

namespace App\Http\Livewire\Reportes;

use Livewire\Component;
use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;

class ReporteCalcularGasol extends Component
{

    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores;

    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
    ];

    public function mount()
    {
        $this->inspectores = ServiciosImportados::groupBy('certificador')->pluck('certificador');
        //$this->talleres = Taller::all();
        $this->talleres = ServiciosImportados::groupBy('taller')->pluck('taller');
    }
    public function render()
    {
        return view('livewire.reportes.reporte-calcular-gasol', [
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
            'taller',
            'precio'
        )
            ->whereBetween('fecha', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();


        $resultados = $resultados->sortBy('taller');
        $resultadosFinales = [];
        $taller = null; // Inicializar $taller

        foreach ($resultados->groupBy(['taller', 'certificador']) as $tallerCertificador => $servicios) {
            // Manejo de certificador nulo
            $certificador = $tallerCertificador;
            if (strpos($tallerCertificador, '|') !== false) {
                list($taller, $certificador) = explode('|', $tallerCertificador);
            }

            if (!isset($resultadosFinales[$taller])) {
                $resultadosFinales[$taller] = [];
            }

            $totalAnuales = $servicios->where('tipoServicio', 2)->count();
            $totalConversiones = $servicios->where('tipoServicio', 1)->count();
            $totalDesmontes = $servicios->where('tipoServicio', 6)->count();
            $totalPrecio = $servicios->sum('precio');

            $resultadosFinales[$taller][] = [
                'certificador' => $certificador,
                'totalAnuales' => $totalAnuales,
                'totalConversiones' => $totalConversiones,
                'totalDesmontes' => $totalDesmontes,
                'totalPrecio' => $totalPrecio,
                'detalles' => $servicios->toArray(),
            ];
        }

        $this->resultados = $resultadosFinales;
        $this->emit('resultadosCalculados', $this->resultados);
    }
}
