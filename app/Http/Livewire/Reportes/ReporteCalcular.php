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
    public $fechaInicio, $fechaFin, $resultados, $resultadosdetalle, $detallesPlacasFaltantes, $talleres, $inspectores, $totalPrecio, $cantidades, $mostrar = false;
    public $ins = [], $taller = [];
    public $selectAll = false;
    public $selectedRows = [];
    public $editando, $tiposServicios = [];
    public $certificacionIds = [];
    public $updatedPrices = [];
    public $selectedTipoServicios = [];
    public $reportePorInspector; //= []
    public $mostrarTablaSimple = false;
    public $semana = false;
    public $inspectorTotals;
    public $reporteTaller, $mostrarTaller = false, $vertaller;
    public $certificaciones, $serviciosImportados; //
    protected $listeners = ['preciosActualizados' => 'recargarDatos'];


    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
    ];

    public function mount()
    {
        $this->inspectores = User::role(['inspector', 'supervisor'])->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
        $this->serviciosImportados = ServiciosImportados::all();
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
        $resultadosdetalle = $this->datosMostrar();
        $totalPrecio = $resultadosdetalle->sum('precio');
        //Cache::put('reporteCalcular', $this->datosMostrar(), now()->addMinutes(10));
        $datosCombinados = $this->datosMostrar();
        $this->totalPrecio = $totalPrecio;
        $this->reportePorInspector = $resultadosdetalle->groupBy('idInspector');  //collect($this->resultadosdetalle)->groupBy('idInspector')->toArray(); 

        $this->detallesPlacasFaltantes   = $this->compararDiscrepancias();
        $this->mostrarTablaSimple = true;
    }

    private function compararDiscrepancias()
    {
        $certificaciones = DB::table('certificacion')
            ->select(
                'certificacion.idVehiculo',
                'certificacion.estado',
                'certificacion.created_at',
                'certificacion.pagado',
                'certificacion.idInspector',
                'certificacion.idTaller',
                'certificacion.idServicio',
                'vehiculo.placa as placa',
            )
            ->join('vehiculo', 'certificacion.idVehiculo', '=', 'vehiculo.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->whereIn('tiposervicio.descripcion', ['Conversión a GNV', 'Revisión anual GNV', 'Desmonte de Cilindro'])
            ->where('certificacion.pagado', 0)
            ->whereIn('certificacion.estado', [3, 1])
            ->where(function ($query) {
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCerti($query);
            })
            ->get();

        $serviciosImportados = DB::table('servicios_importados')
            ->select(
                'servicios_importados.fecha',
                'servicios_importados.placa',
                'servicios_importados.taller',
                'servicios_importados.certificador',
                'servicios_importados.tipoServicio',
            )
            ->where(function ($query) {
                if (!empty($this->ins)) {
                    $query->whereIn('servicios_importados.certificador', $this->ins);
                }

                if (!empty($this->taller)) {
                    $query->whereIn('servicios_importados.taller', $this->taller);
                }
            })
            ->where(function ($query) {
                $this->fechaServImpor($query);
            })
            ->get();

        //$placasCertificaciones = $certificaciones->pluck('placa')->unique();
        // Filtrar servicios importados para que solo incluyan registros únicos
        $serviciosImportadosUnicos = $serviciosImportados->unique(function ($item) {
            return $item->placa . $item->taller . $item->certificador . $item->tipoServicio . $item->fecha;
        });
        //detalles de servicios_importados únicos
        $detallesServiciosImportados = $serviciosImportadosUnicos->map(function ($item) {
            return [
                'placa' => $item->placa,
                'taller' => $item->taller,
                'certificador' => $item->certificador,
                'tipoServicio' => $item->tipoServicio,
                'fecha' => $item->fecha
            ];
        });
        //placas de servicios_importados
        $placasServiciosImportados = $serviciosImportadosUnicos->pluck('placa')->unique();
        // Filtrar las placas que están en servicios_importados pero no en certificacion para los tipos de servicio requeridos
        $placasFaltantes = $placasServiciosImportados->reject(function ($placa) use ($certificaciones) {
            return $certificaciones->where('placa', $placa)
                ->whereNotIn('tipoServicio', ['Conversión a GNV', 'Revisión anual GNV', 'Desmonte de Cilindro'])
                ->isNotEmpty();
        });
        // Detalles de las placas faltantes
        $detallesPlacasFaltantes = $detallesServiciosImportados->filter(function ($item) use ($placasFaltantes) {
            return $placasFaltantes->contains($item['placa']);
        });
        //dd($detallesPlacasFaltantes);
        return $detallesPlacasFaltantes;
    }

    private function datosMostrar()
    {

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
            ->where('certificacion.pagado', 0)
            ->whereIn('certificacion.estado', [3, 1])
            ->where(function ($query) {
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCerti($query);
            })

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
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCeriPendi($query);
            })
            ->get();


        $resultadosdetalle = $certificaciones->concat($certificadosPendientes);

        return $resultadosdetalle;
    }

    private function fechaCerti($query)
    {
        return $query->whereBetween('certificacion.created_at', [
            $this->fechaInicio . ' 00:00:00',
            $this->fechaFin . ' 23:59:59'
        ]);
    }

    private function fechaCeriPendi($query)
    {
        return $query->whereBetween('certificados_pendientes.created_at', [
            $this->fechaInicio . ' 00:00:00',
            $this->fechaFin . ' 23:59:59'
        ]);
    }
    private function fechaServImpor($query)
    {
        return $query->whereBetween('servicios_importados.fecha', [
            $this->fechaInicio . ' 00:00:00',
            $this->fechaFin . ' 23:59:59'
        ]);
    }

    private function agregarFiltros($query)
    {
        if (!empty($this->ins)) {
            $query->whereIn('idInspector', $this->ins);
        }

        if (!empty($this->taller)) {
            $query->whereIn('idTaller', $this->taller);
        }
    }

    /*public function exportarExcel()
    {
        $data = Cache::get('reporteCalcular');
        //dd($data);
        if ($data) {
            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularExport($data), 'ReporteCalcular' . $fecha . '.xlsx');
        }
    }*/

    public function exportarExcel()
    {

        $datosCertificaciones = $this->datosMostrar();
        $datosDiscrepancias = $this->compararDiscrepancias();
        $datosCombinados = $datosCertificaciones->concat($datosDiscrepancias);
        $data = $datosCombinados;
        if ($data) {
            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularExport($data), 'ReporteCalcular' . $fecha . '.xlsx');
        }
    }

    public function calcularReporteSimple()
    {
        $this->validate();
        $resultados = [];
        $tiposServiciosDeseados = ['Revisión anual GNV', 'Conversión a GNV', 'Desmonte de Cilindro', 'Duplicado GNV'];

        $certificaciones = DB::table('certificacion')
            ->select(
                'users.name as nombre',
                'tiposervicio.descripcion as tiposervicio',
                'tiposervicio.id as idTipoServicio',
                'certificacion.estado',
                'certificacion.pagado',
                DB::raw('COUNT(tiposervicio.descripcion) as cantidad_servicio'),
                DB::raw('SUM(certificacion.precio) as total_precio'),
                DB::raw('DAYOFWEEK(certificacion.created_at) as dia_semana')
            )
            ->join('users', 'certificacion.idInspector', '=', 'users.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where('certificacion.pagado', 0)
            ->whereIn('certificacion.estado', [3, 1])
            ->where(function ($query) {
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCerti($query);
            })
            ->groupBy('nombre', 'tiposervicio.descripcion', 'tiposervicio.id', 'certificacion.pagado', 'certificacion.estado', 'dia_semana')
            ->get();

        $certificadosPendientes = DB::table('certificados_pendientes')
            ->select(
                'users.name as nombre',
                'tiposervicio.id as idTipoServicio',
                'certificados_pendientes.estado',
                'certificados_pendientes.pagado',
                DB::raw("'Activación de chip (Anual)' as tiposervicio"),
                DB::raw('COUNT("Activación de chip (Anual)") as cantidad_servicio'),
                DB::raw('SUM(certificados_pendientes.precio) as total_precio'),
                DB::raw('DAYOFWEEK(certificados_pendientes.created_at) as dia_semana')
            )
            ->Join('users', 'certificados_pendientes.idInspector', '=', 'users.id')
            ->Join('servicio', 'certificados_pendientes.idServicio', '=', 'servicio.id')
            ->Join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where('certificados_pendientes.estado', 1)
            ->whereNull('certificados_pendientes.idCertificacion')
            ->where(function ($query) {
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCeriPendi($query);
            })

            ->groupBy('nombre', 'tiposervicio', 'tiposervicio.id', 'certificados_pendientes.pagado', 'certificados_pendientes.estado', 'dia_semana')
            ->get();

        $resultados = []; //collect()
        $tiposServiciosDeseados = ['Revisión anual GNV', 'Conversión a GNV', 'Desmonte de Cilindro', 'Duplicado GNV'];

        foreach ($certificaciones as $certificacion) {
            $key = $certificacion->nombre . '_' . $certificacion->tiposervicio . '_' . $certificacion->idTipoServicio;

            if (!isset($resultados[$key])) {
                $resultados[$key] = (object)[
                    'nombreInspector' => $certificacion->nombre,
                    'tiposervicio' => $certificacion->tiposervicio,
                    'dias' => [
                        1 => 0, // Domingo
                        2 => 0, // Lunes
                        3 => 0, // Martes
                        4 => 0, // Miércoles
                        5 => 0, // Jueves
                        6 => 0, // Viernes
                        7 => 0, // Sábado
                    ],
                    'total' => 0,
                    'total_certificacion' => 0,
                    'pagado' => 0,
                    'estado' => 0,
                ];
            }

            $resultados[$key]->dias[$certificacion->dia_semana] += $certificacion->cantidad_servicio;
            $resultados[$key]->total += $certificacion->cantidad_servicio;
            if (in_array($certificacion->tiposervicio, $tiposServiciosDeseados)) {
                $resultados[$key]->total_certificacion += $certificacion->total_precio;
            }
        }

        foreach ($certificadosPendientes as $certificado) {
            $key = $certificado->nombre . '_' . $certificado->tiposervicio . '_' . $certificado->idTipoServicio;

            if (!isset($resultados[$key])) {
                $resultados[$key] = (object)[
                    'nombreInspector' => $certificado->nombre,
                    'tiposervicio' => $certificado->tiposervicio,
                    'dias' => [
                        1 => 0, // Domingo
                        2 => 0, // Lunes
                        3 => 0, // Martes
                        4 => 0, // Miércoles
                        5 => 0, // Jueves
                        6 => 0, // Viernes
                        7 => 0, // Sábado
                    ],
                    'total' => 0,
                    'total_certificacion' => 0,
                    'pagado' => 0,
                    'estado' => 0,
                ];
            }

            $resultados[$key]->dias[$certificado->dia_semana] += $certificado->cantidad_servicio;
            $resultados[$key]->total += $certificado->cantidad_servicio;
            //$resultados[$key]->total_certificacion += $certificado->total_precio;
            if (in_array($certificado->tiposervicio, $tiposServiciosDeseados)) {
                $resultados[$key]->total_certificacion += $certificado->total_precio;
            }
        }
        

        $inspectorTotals = $this->acumularTotales($resultados);
        $this->inspectorTotals = $inspectorTotals;
        $this->resultados = array_values($resultados); //->toArray()
        Cache::put('reporteCalcularSimple', $this->inspectorTotals, now()->addMinutes(10));
        $this->mostrarTablaSimple = false;
    }

    private function acumularTotales($resultados)
    {
        $inspectorTotals = [];

        foreach ($resultados as $inspector) {

            // Verificamos si ya hemos procesado este inspector
            if (!isset($inspectorTotals[$inspector->nombreInspector])) {
                $inspectorTotals[$inspector->nombreInspector] = [
                    'AnualGnv' => 0,
                    'ConversionGnv' => 0,
                    'Desmonte' => 0,
                    'Duplicado' => 0,
                    'Total' => 0,
                ];
            }

            // Acumulamos los resultados según el tipo de servicio
            switch ($inspector->tiposervicio) {
                case 'Revisión anual GNV':
                    $inspectorTotals[$inspector->nombreInspector]['AnualGnv'] += $inspector->total;
                    break;
                case 'Conversión a GNV':
                    $inspectorTotals[$inspector->nombreInspector]['ConversionGnv'] += $inspector->total;
                    break;
                case 'Desmonte de Cilindro':
                    $inspectorTotals[$inspector->nombreInspector]['Desmonte'] += $inspector->total;
                    break;
                case 'Duplicado GNV':
                    $inspectorTotals[$inspector->nombreInspector]['Duplicado'] += $inspector->total;
                    break;
            }

            // Acumulamos el total general
            $inspectorTotals[$inspector->nombreInspector]['Total'] += $inspector->total_certificacion;
        }
        return $inspectorTotals;
    }

    public function exportarExcelSimple2()
    {
        $data = Cache::get('reporteCalcularSimple');

        if ($data) {
            $exportData = [];

            foreach ($data as $inspectorName => $totals) {
                $exportData[] = [
                    'Inspector' => $inspectorName,
                    'Anual Gnv' => $totals['AnualGnv'],
                    'Conversion Gnv' => $totals['ConversionGnv'],
                    'Desmonte' => $totals['Desmonte'],
                    'Duplicado' => $totals['Duplicado'],
                    'Total' =>  'S/' . number_format($totals['Total'], 2, '.', ''),
                ];
            }

            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularSimpleExport($exportData), 'ReporteCalcular' . $fecha . '.xlsx');
        }
    }
    public function exportarExcelSimple()
{
    $inspectorTotals = $this->inspectorTotals;

    if (!empty($inspectorTotals)) {
        $exportData = [];

        foreach ($inspectorTotals as $inspectorName => $totals) {
            $exportData[] = [
                'Inspector' => $inspectorName,
                'Anual Gnv' => $totals['AnualGnv'],
                'Conversion Gnv' => $totals['ConversionGnv'],
                'Desmonte' => $totals['Desmonte'],
                'Duplicado' => $totals['Duplicado'],
                'Total' =>  'S/' . number_format($totals['Total'], 2, '.', ''),
            ];
        }

        $fecha = now()->format('d-m-Y');
        return Excel::download(new ReporteCalcularSimpleExport($exportData), 'ReporteCalcular' . $fecha . '.xlsx');
    }
}



    public function taller()
    {
        $certificaciones = DB::table('certificacion')
            ->select(
                'certificacion.id',
                'certificacion.idTaller',
                'certificacion.idInspector',
                'certificacion.idServicio',
                'certificacion.estado',
                'certificacion.created_at',
                'certificacion.precio',
                'certificacion.pagado',
                'users.name as nombre',
                'taller.nombre as taller',
                'vehiculo.placa as placa',
                'tiposervicio.descripcion as tiposervicio',


            )
            ->join('users', 'certificacion.idInspector', '=', 'users.id')
            ->join('taller', 'certificacion.idTaller', '=', 'taller.id')
            ->join('vehiculo', 'certificacion.idVehiculo', '=', 'vehiculo.id')
            ->join('servicio', 'certificacion.idServicio', '=', 'servicio.id')
            ->join('tiposervicio', 'servicio.tipoServicio_idtipoServicio', '=', 'tiposervicio.id')
            ->where('certificacion.pagado', 0)
            ->whereIn('certificacion.estado', [3, 1])
            ->where(function ($query) {
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCerti($query);
            })

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
            ->where('certificados_pendientes.estado', 1)
            ->whereNull('certificados_pendientes.idCertificacion')
            ->where(function ($query) {
                $this->agregarFiltros($query);
            })
            ->where(function ($query) {
                $this->fechaCeriPendi($query);
            })

            ->get();

        $vertaller = $certificaciones->concat($certificadosPendientes);
        $totalPrecio = $vertaller->sum('precio');
        $this->vertaller = $vertaller;
        $this->totalPrecio = $totalPrecio;
        // Agrupar por inspector
        $this->reporteTaller = $vertaller->groupBy('idTaller');
    }

    public function ver($certificacionIds, $tiposServicios)
    {
        $this->certificacionIds = $certificacionIds;
        $this->tiposServicios = $tiposServicios;
        $this->editando = true;
    }


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

    public function recargarDatos()
    {
        $this->calcularReporte();
    }
}
