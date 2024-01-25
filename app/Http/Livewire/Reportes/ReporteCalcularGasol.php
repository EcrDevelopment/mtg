<?php

namespace App\Http\Livewire\Reportes;

use App\Exports\ReporteCalcularGasolutionExport;
use Livewire\Component;
use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ReporteCalcularGasol extends Component
{

    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores;
    public $ins, $taller;

    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
    ];

    public function mount()
    {
        //$this->inspectores = ServiciosImportados::groupBy('certificador')->pluck('certificador');
        //$this->talleres = Taller::all();
        //$this->talleres = ServiciosImportados::groupBy('taller')->pluck('taller');
        $this->inspectores = User::role(['inspector', 'supervisor'])->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
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

        // Imprimir valores de los filtros
        //dump($this->ins);
        //dump($this->taller);

        $resultados = ServiciosImportados::with('tipoServicio')
            ->select(
                'fecha',
                'placa',
                'tipoServicio',
                'certificador',
                'taller',
                'precio',
                'serie',
            )
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->where('certificador', $this->ins);
                }
        
                if (!empty($this->taller)) {
                    $query->where('taller', $this->taller);
                }
            })  
            ->whereBetween('fecha', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])                

            ->get();

        // Imprimir resultados para depuración
        //dd($resultados);

        $this->resultados = $resultados;
        $this->emit('resultadosCalculados', $this->resultados);
        Cache::put('reporteGasol_copy', $this->resultados, now()->addMinutes(10));
    }


    public function exportarExcel()
    {
        $data = Cache::get('reporteGasol_copy');

        if ($data) {
            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularGasolutionExport($data), 'Reporte_calcular_gasolution_' . $fecha . '.xlsx');
        }
    }
}
