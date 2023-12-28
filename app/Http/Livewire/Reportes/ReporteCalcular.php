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

    /*public function calcularReporte()
{
    $this->validate();

    $resultados = DB::table('certificacion')
        ->select(
            'certificacion.created_at as fecha',
            'taller.nombre as nombreTaller',
            'users.name as certificador',
            'servicio.precio',
            'certificacion.estado', // Añade más columnas según sea necesario
        )
        ->join('taller', 'certificacion.idTaller', '=', 'taller.id')
        ->join('users', 'certificacion.idInspector', '=', 'users.id')
        ->join('servicio', function ($join) {
            $join->on('certificacion.idServicio', '=', 'servicio.id')
                ->on('certificacion.idTaller', '=', 'servicio.taller_idtaller')
                ->on('certificacion.idServicio', '=', 'servicio.tipoServicio_idtipoServicio');
        })
        ->whereBetween('certificacion.created_at', [
            $this->fechaInicio . ' 00:00:00',
            $this->fechaFin . ' 23:59:59'
        ])
        ->get();

    $serviciosPorInspector = $resultados->groupBy('certificador');

    $resultadosFinales = [];

    foreach ($serviciosPorInspector as $certificador => $servicios) {
        $totalPrecio = $servicios->sum('precio');
        $totalServicios = $servicios->count();

        $resultadosFinales[] = [
            'certificador' => $certificador,
            'totalPrecio' => $totalPrecio,
            'totalServicios' => $totalServicios,
            'detalles' => $servicios->toArray(),
        ];
    }

    $this->resultados = $resultadosFinales;
    $this->emit('resultadosCalculados', $this->resultados);
}*/

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

        // Calcular el total de la columna "precio"
        $totalPrecio = $certificaciones->sum('precio');

        // Agregar el total a los resultados
        //$certificaciones->totalPrecio = $totalPrecio;


        $this->resultados = $certificaciones;
        $this->emit('resultadosCalculados', $this->resultados);
    }
}
