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
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

use Livewire\Component;

class ReporteCalcular extends Component
{
    public $fechaInicio, $fechaFin, $resultados, $talleres, $inspectores, $totalPrecio, $cantidades, $tiposServicio, $mostrar = false;
    public $ins, $taller;
    public $selectAll = false;
    public $selectedRows = [];
    public $editando;
    public $certificacionIds = [];
    public $updatedPrices = [];
    public $selectedTipoServicios = [];


    protected $rules = [
        "fechaInicio" => 'required|date',
        "fechaFin" => 'required|date',
    ];

    public function mount()
    {
        $this->inspectores = User::role(['inspector', 'supervisor'])->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
        $this->tiposServicio = TipoServicio::all()->sortBy('descripcion');
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


        $resultados = $certificaciones->concat($certificadosPendientes);
        $totalPrecio = $resultados->sum('precio');
        $this->resultados = $resultados;
        Cache::put('reporteCalcular', $this->resultados, now()->addMinutes(10));
        $this->totalPrecio = $totalPrecio;
        $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);
    }

    public function exportarExcel()
    {
        $data = Cache::get('reporteCalcular');

        if ($data) {
            $fecha = now()->format('d-m-Y');
            return Excel::download(new ReporteCalcularExport($data), 'ReporteCalcular' . $fecha . '.xlsx');
        }
    }

    public function ver($certificacionIds)
    {
        //dd($certificacionIds);
        $this->certificacionIds = $certificacionIds;
        $this->editando = true;
    }


    public function updatePrecios()
    {
        if (count($this->selectedTipoServicios) > 0) {
            foreach ($this->selectedTipoServicios as $tipoServicioId => $certificacionId) {
                $precioActualizado = $this->updatedPrices[$tipoServicioId] ?? null;

                if ($precioActualizado !== null) {
                    // Encuentra la certificación por su ID
                    $certificacion = Certificacion::find($certificacionId);

                    if ($certificacion) {
                        // Encuentra el tipo de servicio por su ID
                        $tipoServicio = TipoServicio::find($tipoServicioId);

                        if ($tipoServicio) {
                            // Actualiza el precio del tipo de servicio en la certificación
                            $certificacion->tipoServicios()->updateExistingPivot($tipoServicio->id, [
                                'precio' => $precioActualizado,
                            ]);
                        }
                    }
                }
            }

            // Restablece las propiedades después de actualizar los precios
            $this->certificacionIds = [];
            $this->selectedTipoServicios = [];
            $this->updatedPrices = [];

            // Cierra el modal
            $this->editando = false;
        }
    }



    public function toggleSelectAll()
    {
        $this->selectAll = !$this->selectAll;

        // Si el checkbox de selección está marcado, seleccionar todas las filas
        if ($this->selectAll) {
            $this->selectedRows = range(0, count($this->certificacionesInspector) - 1);
        } else {
            // Si el checkbox de selección está desmarcado, deseleccionar todas las filas
            $this->selectedRows = [];
        }
    }

    /*public function precios()
    {
        // Asegurarse de obtener el objeto User solo si hay un ID válido seleccionado
        if (!empty($this->selectedInspectorId)) {
            $this->selectedInspector = User::find($this->selectedInspectorId);

            // Obtener los precios del inspector seleccionado desde la caché
            $this->preciosInspector = $this->precioServicios[$this->selectedInspectorId] ?? [];
            info($this->preciosInspector);
            // Emitir el evento para actualizar la interfaz de usuario con los precios actuales
            $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);            
        }
        $this->editando = true;
        //dd($this->precioServicios);
    }*/


    /*public function updatePrecios()
    {
        //dd('Evento emitido');
        if (!empty($this->selectedInspectorId)) {
            $this->precioServicios[$this->selectedInspectorId] = $this->preciosInspector;
            Cache::put('precioServicios', $this->precioServicios, now()->addMinutes(10));            
            // Actualiza los precios en la variable local
            $this->preciosInspector = $this->precioServicios[$this->selectedInspectorId] ?? [];
            $this->emit('resultadosCalculados', $this->resultados, $this->cantidades, $this->totalPrecio);
            //dd('Precios actualizados y evento emitido', $this->precioServicios, Cache::get('precioServicios'));

            $this->selectedInspectorId  = null;
            $this->preciosInspector = [];
        }


        $this->editando = false;
    }*/
}
