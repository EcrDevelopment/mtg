<?php

namespace App\Http\Livewire\Reportes;

use App\Models\Certificacion;
use App\Models\CertificacionPendiente;
use App\Models\ServiciosImportados;
use App\Models\Taller;
use App\Models\TipoServicio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Exports\ReporteCalcularExport;
use App\Models\Servicio;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores, $totalPrecio, $cantidades, $mostrar = false;
    public $ins, $taller;
    public $selectAll = false;
    public $selectedRows = [];
    public $editando, $tiposServicios = [];
    public $certificacionIds = [];
    public $updatedPrices = [];
    public $selectedTipoServicios = [];
    public $reportePorInspector; //= []
    protected $listeners = ['preciosActualizados' => 'recargarDatos'];


    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
    ];

    public function mount()
    {
        $this->inspectores = User::role(['inspector', 'supervisor'])->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
        //$this->tiposServicio = TipoServicio::all()->sortBy('descripcion');
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
                'tiposervicio.descripcion as tiposervicio',
                'material.numSerie as matenumSerie',


            )
            ->join('users', 'certificacion.idInspector', '=', 'users.id')
            ->join('taller', 'certificacion.idTaller', '=', 'taller.id')
            ->join('vehiculo', 'certificacion.idVehiculo', '=', 'vehiculo.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->leftJoin('serviciomaterial', 'certificacion.id', '=', 'serviciomaterial.idCertificacion')
            ->leftJoin('material', 'serviciomaterial.idMaterial', '=', 'material.id')
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->where('certificacion.idInspector', $this->ins);
                }

                if (!empty($this->taller)) {
                    $query->where('certificacion.idTaller', $this->taller);
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
                DB::raw("'Activación de chip (Anual)' as tiposervicio")
            )

            ->leftJoin('users', 'certificados_pendientes.idInspector', '=', 'users.id')
            ->leftJoin('taller', 'certificados_pendientes.idTaller', '=', 'taller.id')
            ->leftJoin('vehiculo', 'certificados_pendientes.idVehiculo', '=', 'vehiculo.id')
            ->leftJoin('servicio', 'certificados_pendientes.idServicio', '=', 'servicio.id')
            //->leftJoin('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where('certificados_pendientes.estado', 1)
            ->whereNull('certificados_pendientes.idCertificacion')
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->where('certificados_pendientes.idInspector', $this->ins);
                }

                if (!empty($this->taller)) {
                    $query->where('certificados_pendientes.idTaller', $this->taller);
                }
            })
            ->whereBetween('certificados_pendientes.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])

            ->get();

        //dd($certificadosPendientes);
        $resultados = $certificaciones->concat($certificadosPendientes);
        $totalPrecio = $resultados->sum('precio');
        $this->resultados = $resultados;
        Cache::put('reporteCalcular', $this->resultados, now()->addMinutes(10));
        $this->totalPrecio = $totalPrecio;
        // Agrupar por inspector
        $this->reportePorInspector = $resultados->groupBy('idInspector');
    }

    public function exportarExcel()
    {
        $data = Cache::get('reporteCalcular');

        if ($data) {
            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularExport($data), 'ReporteCalcular' . $fecha . '.xlsx');
        }
    }

    public function ver($certificacionIds, $tiposServicios)
    {
        $this->certificacionIds = $certificacionIds;
        $this->tiposServicios = $tiposServicios;
        $this->editando = true;
    }


    /*public function updatePrecios()
    {
        //$servicios=$this->resultados->whereIn('id', $this->certificacionIds)->whereIn('price', [150, 200]);;
       
        if (count($this->updatedPrices) > 0) {
            foreach ($this->updatedPrices as $key => $nuevoPrecio) {
                $servs=$this->resultados->whereIn('id', $this->certificacionIds)->where('tiposervicio', $key);
                 //dd($servicios);   
                foreach($servs as $seleccionado){
                    Certificacion::find($seleccionado["id"])->update(['precio'=>$nuevoPrecio]); 
                    //CertificacionPendiente::find($seleccionado["id"])->update(['precio' => $nuevoPrecio]);                   
                }

                //dd($seleccionado);
            }
            $this->reset(['resultados','updatedPrices','certificacionIds']);
            $this->calcularReporte();
            $this->editando = false;          
            
            
        }
    }*/

    public function updatePrecios()
    {
        if (count($this->updatedPrices) > 0) {
            foreach ($this->updatedPrices as $key => $nuevoPrecio) {
                $certificacionIds = $this->certificacionIds;

                // Actualizar precios en Certificacion
                Certificacion::whereIn('id', $certificacionIds)
                    ->whereHas('servicio', function ($query) use ($key) {
                        $query->whereHas('tipoServicio', function ($query) use ($key) {
                            $query->where('descripcion', $key);
                        });
                    })
                    ->update(['precio' => $nuevoPrecio]);

                // Actualizar precios en CertificacionPendiente
                CertificacionPendiente::whereIn('id', $certificacionIds)
                    ->update(['precio' => $nuevoPrecio]);
            }

            // Emitir evento para indicar que los precios han sido actualizados
            $this->emit('preciosActualizados');
            // Refrescar solo la sección de la tabla
            $this->dispatchBrowserEvent('refresh-table');
            // Resetear propiedades y recalcular reporte
            $this->reset(['resultados', 'updatedPrices', 'certificacionIds']);
            $this->calcularReporte();
            $this->editando = false;
        }
    }

    public function recargarDatos()
    {
        $this->calcularReporte();

        // Emitir un evento para indicar que los datos se han recalculado
        $this->emit('datosRecargados');
    }



    /*public function toggleSelectAll()
    {
        $this->selectAll = !$this->selectAll;

        // Si el checkbox de selección está marcado, seleccionar todas las filas
        if ($this->selectAll) {
            $this->selectedRows = range(0, count($this->certificacionesInspector) - 1);
        } else {
            // Si el checkbox de selección está desmarcado, deseleccionar todas las filas
            $this->selectedRows = [];
        }
    }*/
}
