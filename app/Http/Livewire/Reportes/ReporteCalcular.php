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
use App\Exports\ReporteCalcularSimpleExport;
use App\Models\Servicio;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores, $totalPrecio, $cantidades, $mostrar = false;
    public $ins = [], $taller = [];
    public $selectAll = false;
    public $selectedRows = [];
    public $editando, $tiposServicios = [];
    public $certificacionIds = [];
    public $updatedPrices = [];
    public $selectedTipoServicios = [];
    public $reportePorInspector; //= []
    public $mostrarTablaSimple = false;
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
                //'material.numSerie as matenumSerie',
                DB::raw('(SELECT material.numSerie FROM serviciomaterial 
                LEFT JOIN material ON serviciomaterial.idMaterial = material.id 
                WHERE serviciomaterial.idCertificacion = certificacion.id LIMIT 1) as matenumSerie')


            )
            ->join('users', 'certificacion.idInspector', '=', 'users.id')
            ->join('taller', 'certificacion.idTaller', '=', 'taller.id')
            ->join('vehiculo', 'certificacion.idVehiculo', '=', 'vehiculo.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            //->leftJoin('serviciomaterial', 'certificacion.id', '=', 'serviciomaterial.idCertificacion')
            //->leftJoin('material', 'serviciomaterial.idMaterial', '=', 'material.id')
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->whereIn('certificacion.idInspector', $this->ins);
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
                    $query->whereIn('certificados_pendientes.idInspector', $this->ins);
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
        $this->mostrarTablaSimple = true;
    }

    public function exportarExcel()
    {
        $data = Cache::get('reporteCalcular');

        if ($data) {
            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularExport($data), 'ReporteCalcular' . $fecha . '.xlsx');
        }
    }

    public function calcularReporteSimple()
    {
        $this->validate();

        $certificaciones = DB::table('certificacion')
            ->select(
                'users.name as nombre',
                'tiposervicio.descripcion as tiposervicio',
                'certificacion.estado',
                'certificacion.pagado',
                DB::raw('COUNT(tiposervicio.descripcion) as cantidad_servicio'),
                DB::raw('SUM(certificacion.precio) as total_precio')
            )
            ->join('users', 'certificacion.idInspector', '=', 'users.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->whereIn('certificacion.idInspector', $this->ins);
                }

                if (!empty($this->taller)) {
                    $query->where('certificacion.idTaller', $this->taller);
                }
            })
            ->whereBetween('certificacion.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])
            ->groupBy('nombre', 'tiposervicio.descripcion', 'certificacion.pagado', 'certificacion.estado')
            ->get();

        $certificadosPendientes = DB::table('certificados_pendientes')
            ->select(
                'users.name as nombre',
                'certificados_pendientes.estado',
                'certificados_pendientes.pagado',
                DB::raw("'Activación de chip (Anual)' as tiposervicio"),
                DB::raw('COUNT("Activación de chip (Anual)") as cantidad_servicio'),
                DB::raw('SUM(certificados_pendientes.precio) as total_precio'),
            )

            ->leftJoin('users', 'certificados_pendientes.idInspector', '=', 'users.id')
            ->leftJoin('servicio', 'certificados_pendientes.idServicio', '=', 'servicio.id')
            //->leftJoin('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where('certificados_pendientes.estado', 1)
            ->whereNull('certificados_pendientes.idCertificacion')
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->whereIn('certificados_pendientes.idInspector', $this->ins);
                }

                if (!empty($this->taller)) {
                    $query->where('certificados_pendientes.idTaller', $this->taller);
                }
            })
            ->whereBetween('certificados_pendientes.created_at', [
                $this->fechaInicio . ' 00:00:00',
                $this->fechaFin . ' 23:59:59'
            ])

            ->groupBy('nombre', 'tiposervicio', 'certificados_pendientes.pagado', 'certificados_pendientes.estado')
            ->get();

        $resultados = $certificaciones->concat($certificadosPendientes)->groupBy('nombre');

        $this->resultados = $resultados;
        Cache::put('reporteCalcularSimple', $this->resultados, now()->addMinutes(10));
        $this->mostrarTablaSimple = false;
    }

    public function exportarExcelSimple()
    {
        $data = Cache::get('reporteCalcularSimple');

        if ($data) {
            $exportData = [];

            foreach ($data as $inspector => $servicios) {
                $serviciosCollection = collect($servicios);

                $exportData[] = [
                    'nombre' => $inspector,
                    'AnualGNV' => $serviciosCollection->where('tiposervicio', 'Revisión anual GNV')->sum('cantidad_servicio'),
                    'ConversionGnv' => $serviciosCollection->where('tiposervicio', 'Conversión a GNV')->sum('cantidad_servicio'),
                    'AnualGLP' => $serviciosCollection->where('tiposervicio', 'Revisión anual GLP')->sum('cantidad_servicio'),
                    'ConversionGLP' => $serviciosCollection->where('tiposervicio', 'Conversión a GLP')->sum('cantidad_servicio'),
                    'modi' => $serviciosCollection->where('tiposervicio', 'Modificación')->sum('cantidad_servicio'),
                    'desmonte' => $serviciosCollection->where('tiposervicio', 'Desmonte de Cilindro')->sum('cantidad_servicio'),
                    'activacion' => $serviciosCollection->where('tiposervicio', 'Activación de chip (Anual)')->sum('cantidad_servicio'),
                    'duplicadoGNV' => $serviciosCollection->where('tiposervicio', 'Duplicado GNV')->sum('cantidad_servicio'),
                    'ConverChip' => $serviciosCollection->where('tiposervicio', 'Conversión a GNV + Chip')->sum('cantidad_servicio'),
                    'chip' => $serviciosCollection->where('tiposervicio', 'Chip por deterioro')->sum('cantidad_servicio'),
                    'preGNV' => $serviciosCollection->where('tiposervicio', 'Pre-conversión GNV')->sum('cantidad_servicio'),
                    'preGLP' => $serviciosCollection->where('tiposervicio', 'Pre-conversión GLP')->sum('cantidad_servicio'),
                    'total_precio' => $serviciosCollection
                        ->where('pagado', 0)
                        ->whereIn('estado', [1, 3])
                        ->sum('total_precio'),
                ];
            }

            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularSimpleExport($exportData), 'ReporteCalcular' . $fecha . '.xlsx');
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

            $this->reset(['resultados', 'updatedPrices', 'certificacionIds']);
            $this->calcularReporte();
            $this->editando = false;
        }
    }

    public function toggleSelectAll($inspectorId)
    {
        $this->selectAll[$inspectorId] = !$this->selectAll[$inspectorId];

        if ($this->selectAll[$inspectorId]) {
            $this->selectedRows[$inspectorId] = array_keys($this->reportePorInspector[$inspectorId]->toArray());
        } else {
            $this->selectedRows[$inspectorId] = [];
        }
    }

    public function recargarDatos()
    {
        $this->calcularReporte();
    }
}
