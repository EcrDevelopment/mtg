<?php

namespace App\Http\Livewire\Reportes;

use App\Models\Certificacion;
use App\Models\CertificacionPendiente;
use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores, $totalPrecio, $cantidades;
    public $ins, $taller;
    public $selectAll = false;
    public $selectedItems = [];

    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
        //"ins" => 'numeric',
    ];

    public function mount()
    {
        $this->inspectores = User::role(['inspector', 'supervisor'])->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
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
                'certificacion.estado',
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
            ->where(function ($query) {
                if(!empty($this->ins) && !empty($this->taller)){
                $query->where('certificacion.idInspector', $this->ins)
                      ->orWhere('certificacion.idTaller', $this->taller);
                }
            })
            ->whereBetween('certificacion.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            
            ->get();

        $certificadosPendientes = DB::table('certificados_pendientes')
            ->select(
                'certificados_pendientes.id',
                'certificados_pendientes.idTaller',
                'certificados_pendientes.idInspector',
                'certificados_pendientes.idVehiculo',
                'certificados_pendientes.idServicio',
                'certificados_pendientes.estado',
                'certificados_pendientes.created_at',
                'certificados_pendientes.pagado',
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
            ->where(function ($query) {
                if(!empty($this->ins) && !empty($this->taller)){
                    $query->where('certificados_pendientes.idInspector', $this->ins)
                      ->orWhere('certificados_pendientes.idTaller', $this->taller);
                }                
            })
            ->whereBetween('certificados_pendientes.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            
            ->get();


        $resultados = $certificaciones->concat($certificadosPendientes);
        $totalPrecio = $resultados->sum('precio');
        $this->resultados = $resultados;
        //dd($resultados);
        //$this->cantidades = $cantidades;
        $this->totalPrecio = $totalPrecio;
        $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);
    }

    /*public function calcularReporte()
    {
        $this->validate();

        $certificaciones = Certificacion::with(['Inspector', 'Taller', 'Vehiculo', 'Servicio.tipoServicio'])
            ->whereBetween('created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();

        $certificadosPendientes = CertificacionPendiente::with(['Inspector', 'Taller', 'Vehiculo', 'Servicio.tipoServicio'])
            ->where('estado', 1)
            ->whereNull('idCertificacion')
            ->whereBetween('created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->get();

        $resultados = $certificaciones->concat($certificadosPendientes);

        $this->resultados = $resultados->groupBy('idTaller');

        $this->cantidades = $resultados->groupBy(['Inspector.name', 'Servicio.tipoServicio.descripcion'])->map(function ($items) {
            return [
                'cantidad' => $items->count(),
            ];
        });

        $this->totalPrecio = $resultados->sum('precio');
    }*/



    public function toggleSelectAll($taller)
    {
        $this->selectAll[$taller] = !$this->selectAll[$taller];

        $currentGroupIds = $this->resultados->where('idTaller', $taller)->flatten()->pluck('id')->map(function ($id) {
            return (string) $id;
        })->toArray();

        if ($this->selectAll[$taller]) {
            $this->selectedItems = array_merge($this->selectedItems, $currentGroupIds);
        } else {
            $this->selectedItems = array_diff($this->selectedItems, $currentGroupIds);
        }
    }

    public function actualizarCertificaciones()
    {
        if (!empty($this->selectedItems)) {
            Certificacion::whereIn('id', $this->selectedItems)->update(['pagado' => 1]);

            // Actualizar los resultados después de la actualización
            $this->calcularReporte();
        }

        // Restablecer el estado de selección
        $this->selectAll = false;
        $this->selectedItems = [];
    }
}
