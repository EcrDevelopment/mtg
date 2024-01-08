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
            'precio',
            'serie',
        )
            ->whereBetween('fecha', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();

        $this->resultados = $resultados;
        $this->emit('resultadosCalculados', $this->resultados);
    }

}
