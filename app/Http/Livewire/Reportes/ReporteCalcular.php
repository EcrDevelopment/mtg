<?php

namespace App\Http\Livewire\Reportes;

use App\Models\Certificacion;
use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores, $totalPrecio, $cantidades;
    public $selectAll = false;
    public $selectedItems = [];

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
                'certificacion.id',
                'certificacion.idTaller',
                'certificacion.idInspector',
                'certificacion.idVehiculo',
                'certificacion.idServicio',
                'certificacion.created_at',
                'certificacion.precio',
                'certificacion.pagado',
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

        // Obtener certificados pendientes
        $certificadosPendientes = DB::table('certificados_pendientes')
            ->select(
                'certificados_pendientes.idTaller',
                'certificados_pendientes.idInspector',
                'certificados_pendientes.idVehiculo',
                'certificados_pendientes.idServicio',
                'certificados_pendientes.created_at',
                'certificados_pendientes.precio',
                'users.name as nombre',
                'taller.nombre as taller',
                'vehiculo.placa as placa',
                'tiposervicio.descripcion as tiposervicio'
            )
            ->leftJoin('users', 'certificados_pendientes.idInspector', '=', 'users.id')
            ->leftJoin('taller', 'certificados_pendientes.idTaller', '=', 'taller.id')
            ->leftJoin('vehiculo', 'certificados_pendientes.idVehiculo', '=', 'vehiculo.id')
            ->leftJoin('servicio', 'certificados_pendientes.idServicio', '=', 'servicio.id')
            ->leftJoin('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where('certificados_pendientes.estado', 1)
            ->whereNull('certificados_pendientes.idCertificacion')
            ->whereBetween('certificados_pendientes.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();


        $resultados = $certificaciones->concat($certificadosPendientes); // Combinar los resultados  
        // Actualizar el estado 'pagado' de los elementos seleccionados
        if (!empty($this->selectedItems)) {
            Certificacion::whereIn('id', $this->selectedItems)->update(['pagado' => 1]);
        }
        // Restablecer los checkboxes
        $this->selectAll = false;
        $this->selectedItems = [];
        $cantidades = $resultados->groupBy(['nombre', 'tiposervicio'])->map(function ($items) {
            return [
                'cantidad' => $items->count(),
            ];
        });

        /*$cantidades = $certificaciones->groupBy(['nombre', 'tiposervicio'])->map(function ($items) {
            return [
                'cantidad' => $items->count(),
            ];
        });*/

        $totalPrecio = $resultados->sum('precio'); // Calcular el total de la columna "precio"
        $this->resultados = $resultados;
        $this->cantidades = $cantidades;
        $this->totalPrecio = $totalPrecio;
        // Emitir evento para actualizar la interfaz
        $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);

        /*
        $totalPrecio = $certificaciones->sum('precio'); // Calcular el total de la columna "precio"
        //$certificaciones->totalPrecio = $totalPrecio;

        $this->resultados = $certificaciones;
        $this->cantidades = $cantidades;
        $this->totalPrecio = $totalPrecio;
        $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);*/
    }
}
