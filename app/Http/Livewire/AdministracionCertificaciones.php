<?php

namespace App\Http\Livewire;

use App\Models\CertifiacionExpediente;
use App\Models\Certificacion;
use App\Models\Expediente;
use App\Models\Imagen;
use App\Models\Taller;
use App\Models\TipoServicio;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class AdministracionCertificaciones extends Component
{


    use WithPagination;

    public $search, $sort, $direction, $cant, $user, $fechaFin, $dateOptions, $inspectores, $ins, $servicio, $tipos, $talleres, $ta, $fecIni, $fecFin;
    public $editando, $expediente, $identificador, $tipoServicio;
    public $documentos = [];
    public $files = [];

    protected $listeners = ['render', 'delete', 'anular'];

    protected $queryString = [
        'cant' => ['except' => '10'],
        'sort' => ['except' => 'certificacion.id'],
        'direction' => ['except' => 'desc'],
        'search' => ['except' => ''],
    ];



    protected $casts = [
        'fechaFin' => 'datetime:d-m-Y',
    ];

    public function mount()
    {
        $this->user = Auth::id();
        $this->cant = "10";
        $this->sort = 'certificacion.id';
        $this->direction = "desc";
        $this->fechaFin = date('d/m/Y');
        $this->inspectores = User::role(['inspector', 'supervisor'])->where('id', '!=', Auth::id())->orderBy('name')->get();
        $this->talleres = Taller::all()->sortBy('nombre');
        $this->tipos = TipoServicio::all();
        $this->ins = '';
        $this->fecIni = '';
        $this->fecFin = '';
        $this->servicio = '';
        $this->ta = '';
    }

    public function render()
    {
        $certificaciones = Certificacion::numFormato($this->search)
            ->placaVehiculo($this->search)
            ->idInspector($this->ins)
            ->tipoServicio($this->servicio)
            ->idTaller($this->ta)
            ->rangoFecha($this->fecIni, $this->fecFin)
            ->orderBy($this->sort, $this->direction)
            ->paginate($this->cant);
        return view('livewire.administracion-certificaciones', compact('certificaciones'));
    }


    public function order($sort)
    {
        if ($this->sort = $sort) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort = $sort;
            $this->direction = 'asc';
        }
    }

    public function anular(Certificacion $certificacion)
    {
        $certificacion->Hoja->update(['estado' => 5]); // estado anulado en MATERIAL
        $certificacion->update(['estado' => 2]); //estado anulado en CERTIFICACION
        $this->emitTo('administracion-certificaciones', 'render');
    }


    public function cambiaEstadoDeMateriales(Collection $materiales, User $inspector)
    {
        $materiales->each(function ($item, $key) use ($inspector) {
            $item->update(['estado' => 3, "ubicacion" => "En poder de " . $inspector->name]);
        });
    }

    public function delete(Certificacion $certificacion)
    {

        if ($certificacion->Hoja) {
            $certExp = CertifiacionExpediente::where('idCertificacion', $certificacion->id)->first();
            if ($certExp) {
                $expe = Expediente::find($certExp->idExpediente);
                if ($expe) {

                    $imgs = Imagen::where('Expediente_idExpediente', '=', $expe->id)->get();
                    foreach ($imgs as $img) {
                        Storage::delete($img->ruta);
                    }
                    $expe->delete();
                }
            }

            $this->cambiaEstadoDeMateriales($certificacion->Materiales, $certificacion->Inspector);
            $certificacion->delete();
        } else {
            if ($certificacion->delete()) {
                $this->emitTo('administracion-certificaciones', 'render');
                $this->emit("minAlert", ["titulo" => "AVISO DEL SISTEMA", "mensaje" => "Se elimino tu servicio pero no se cambio el estado de su formato", "icono" => "warning"]);
            }
        }
    }




    public function generarRuta($cer)
    {
        $certificacion = Certificacion::find($cer);
        $ver = "";
        $descargar = "";
        if ($certificacion) {
            $tipoSer = $certificacion->Servicio->tipoServicio->id;
            switch ($tipoSer) {
                case 1:
                    $ver = route('certificadoInicial', ['id' => $certificacion->id]);
                    break;
                case 2:
                    $ver = route('certificado', ['id' => $certificacion->id]);
                    break;
                default:
                    # code...
                    break;
            }
        }

        return $ver;
    }
    public function generarRutaDescarga($cer)
    {
        $certificacion = Certificacion::find($cer);
        $descargar = "";
        if ($certificacion) {
            $tipoSer = $certificacion->Servicio->tipoServicio->id;
            switch ($tipoSer) {
                case 1:
                    $descargar = route('descargarInicial', ['id' => $certificacion->id]);
                    break;
                case 2:
                    $descargar = route('descargarCertificado', ['id' => $certificacion->id]);
                    break;
                default:
                    # code...
                    break;
            }
        }

        return $descargar;
    }


    public function edit(Certificacion $cert)
    {
       // dd($cert);
        $cert_ex= CertifiacionExpediente::where('idCertificacion',$cert->id)->first();
        
       // dd($cert_ex);

        $expediente=Expediente::findOrFail($cert_ex->idExpediente);
        if ($expediente->estado == 2) {
            $this->pasaDatosExpediente($expediente);
            $this->editando = true;
        } else {
            $this->pasaDatosExpediente($expediente);
            $this->editando = true;
        }
    }

    // Esta función se llama para cargar datos del expediente en el modal de edición.
    public function pasaDatosExpediente(Expediente $expediente)
    {
        $this->expediente = $expediente;
        $this->files = Imagen::where('Expediente_idExpediente', '=', $expediente->id)->whereIn('extension', ['jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff', 'bmp'])->get();
        //dd($this->files);
        $this->documentos = Imagen::where('Expediente_idExpediente', '=', $expediente->id)->whereIn('extension', ['pdf', 'xlsx', 'xls', 'docx', 'doc'])->get();
        $this->identificador = rand();
    }
}
