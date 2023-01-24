<?php

namespace App\Http\Livewire;

use App\Models\Certificacion;
use App\Models\Equipo;
use App\Models\EquiposVehiculo;
use App\Models\Material;
use App\Models\Servicio as ModelServicio;
use App\Models\ServicioMaterial;
use App\Models\Taller;
use App\Models\TipoEquipo;
use App\Models\vehiculo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Servicio extends Component
{   
    

    //Definiendo Variables de vehiculo
    public $placa,$categoria,$marca,$modelo,$version,$anioFab,$numSerie,$numMotor,
    $cilindros,$cilindrada,$combustible,$ejes,$ruedas,$asientos,$pasajeros,
    $largo,$ancho,$altura,$color,$pesoNeto,$pesoBruto,$cargaUtil;

    //Definiendo Variables de equipos
    
    public $tiposDisponibles=[];
    public $tipoEquipo,$equipoSerie,$equipoMarca,$equipoModelo,$equipoCapacidad;
    public $equipos=[];

    //Variables del servicio
    public $formatosGnvDisponibles;
    public $listaVehiculos=[];
    public $talleres,$servicios,$serv,$tipoServicio,$taller,$ruta,$open,$formularioVehiculo,$vehiculoServicio,$rutaDes;



    //variables del certificado
    public $servicioCertificado,$numSugerido;   
    protected $rules=[
                    "taller"=>"required|numeric|min:1",
                    "serv"=>"required|numeric|min:1",
                    "placa"=>"required|min:6",
                    "categoria"=>"required|min:2",
                    "marca"=>"required|min:2",
                    "modelo"=>"required|min:2",
                    "version"=>"required|min:2", 
                    "anioFab"=>"required|numeric|min:1900",                  
                    "numSerie"=>"required|min:2",
                    "numMotor"=>"required|min:2",
                    "cilindros"=>"required|numeric|min:1",
                    "cilindrada"=>"required|numeric|min:1",
                    "combustible"=>"required|min:2", 
                    "ejes"=>"required|numeric|min:1",   
                    "ruedas"=>"required|numeric|min:1",    
                    "asientos"=>"required|numeric|min:1",      
                    "pasajeros"=>"required|numeric|min:1",   
                    "largo"=>"required|numeric",   
                    "ancho"=>"required|numeric",
                    "altura"=>"required|numeric",
                    "color"=>"required|min:2",
                    "pesoNeto"=>"required|numeric",
                    "pesoBruto"=>"required|numeric",
                    "cargaUtil"=>"required|numeric",
                    ];

    

    
    public function render()
    {
        return view('livewire.servicio');
    }
    
    public function mount(){
        //$this->servicios=ModelServicio::make();
        $this->talleres=Taller::all();
        $this->taller=Taller::make();        
        $this->listaTiposDisponibles();
        $this->open=false;
        $this->formularioVehiculo=true;
        $this->formatosGnvDisponibles=Material::where([['estado',3],['idUsuario',Auth::id()],['idTipoMaterial',1]])->get();   
        //$this->numSugerido=$this->numFormatoSugerido();     
    }

    public function updated($propertyName){

        $this->validateOnly($propertyName);

    }

    public function updatedTaller($val){
        $this->servicios=ModelServicio::where("taller_idtaller",$val)->get();
        $this->reset(["serv"]);
    }  

    public function updatedServ($val){
        
        if($val){
            $this->tipoServicio=ModelServicio::find($val)->tipoServicio;            
            $this->sugeridoSegunTipo($this->tipoServicio->id);  
        }else{
            $this->tipoServicio=null;
        }       
    }
        
    //muestra el número de formato sugerido según el tipo de servicio
    public function sugeridoSegunTipo($tipoServ){
        $formatoGnv=1;        
        $formatoGlp=3;
        if($tipoServ){
            switch ($tipoServ) {
                case 1:
                    $this->numSugerido=$this->formatoSugerido($formatoGnv);
                break;
                case 2:
                    $this->numSugerido=$this->formatoSugerido($formatoGnv);
                break;
                case 3:
                    $this->numSugerido=$this->formatoSugerido($formatoGlp);
                break;
                case 4:
                    $this->numSugerido=$this->formatoSugerido($formatoGlp);
                break;                
                default:
                    $this->numSugerido=0;
                break;
            }
        }
    }
    

    public function guardaVehiculo(){
        $this->validate();
        $vehiculo=vehiculo::create([            
                                    "placa"=>strtoupper($this->placa),
                                    "categoria"=>strtoupper($this->categoria),
                                    "marca"=>strtoupper($this->marca),
                                    "modelo"=>strtoupper($this->modelo),
                                    "version"=>strtoupper($this->version),
                                    "anioFab"=>$this->anioFab,
                                    "numSerie"=>strtoupper($this->numSerie),
                                    "numMotor"=>strtoupper($this->numMotor),
                                    "cilindros"=>$this->cilindros,
                                    "cilindrada"=>$this->cilindrada,
                                    "combustible"=>strtoupper($this->combustible),
                                    "ejes"=>$this->ejes,
                                    "ruedas"=>$this->ruedas,
                                    "asientos"=>$this->asientos,
                                    "pasajeros"=>$this->pasajeros,
                                    "largo"=>$this->largo,
                                    "ancho"=>$this->ancho,
                                    "altura"=>$this->altura,
                                    "color"=>strtoupper($this->color),
                                    "pesoNeto"=>$this->pesoNeto,
                                    "pesoBruto"=>$this->pesoBruto,  
                                    "cargaUtil"=>$this->cargaUtil,          
                                    ]);
        //$this->emit("alert","El vehículo con placa ".$vehiculo->placa." se registro correctamente.");
        $this->formularioVehiculo=false;
        $this->vehiculoServicio=$vehiculo;
        $this->emit('alert','El vehículo con placa '.$vehiculo->placa.' se registro correctamente.');
    }

    public function actualizarVehiculo(){
            $this->validate();
             $this->vehiculoServicio->update(["placa"=>strtoupper($this->placa),
                                            "categoria"=>strtoupper($this->categoria),
                                            "marca"=>strtoupper($this->marca),
                                            "modelo"=>strtoupper($this->modelo),
                                            "version"=>strtoupper($this->version),
                                            "anioFab"=>$this->anioFab,
                                            "numSerie"=>strtoupper($this->numSerie),
                                            "numMotor"=>strtoupper($this->numMotor),
                                            "cilindros"=>$this->cilindros,
                                            "cilindrada"=>$this->cilindrada,
                                            "combustible"=>strtoupper($this->combustible),
                                            "ejes"=>$this->ejes,
                                            "ruedas"=>$this->ruedas,
                                            "asientos"=>$this->asientos,
                                            "pasajeros"=>$this->pasajeros,
                                            "largo"=>$this->largo,
                                            "ancho"=>$this->ancho,
                                            "altura"=>$this->altura,
                                            "color"=>strtoupper($this->color),
                                            "pesoNeto"=>$this->pesoNeto,
                                            "pesoBruto"=>$this->pesoBruto,  
                                            "cargaUtil"=>$this->cargaUtil,]); 
        $this->formularioVehiculo=false;   
        $this->emit("alert","Los datos del vehículo se actualizaron correctamente");
              
    }    

    public function guardaEquipos(){
        $this->validate([
                        "tipoEquipo"=>"required|numeric|min:1"
                        ]);
        switch ($this->tipoEquipo) {
            case 1:
                $this->salvaDatosChip();
                $this->listaTiposDisponibles();
            break;
            case 2:
                $this->salvaDatosReductor();
                $this->listaTiposDisponibles();
            break;
            case 3:
                $this->salvaDatosTanque();
                $this->listaTiposDisponibles();
            break;
            
            default:
                $this->emit("alert","ocurrio un error al guardar los datos");
                break;
        }
    }

    public function salvaDatosTanque(){
        $this->validate([
                        "equipoSerie"=>"required|min:1",
                        "equipoMarca"=>"required|min:1",
                        "equipoCapacidad"=>"required|numeric|min:1"
                        ]);
        $equipo=new Equipo();
        $equipo->idTipoEquipo=$this->tipoEquipo;
        $equipo->numSerie=strtoupper($this->equipoSerie);
        $equipo->marca=strtoupper($this->equipoMarca);
        $equipo->capacidad=strtoupper($this->equipoCapacidad);

        array_push($this->equipos,$equipo);   
        
        $this->reset(["equipoSerie","equipoMarca","equipoModelo","equipoCapacidad","tipoEquipo"]);      
        $this->open=false;
        $this->emit("alert","El ".$equipo->tipo->nombre." con serie ".$equipo->numSerie." se añadió correctamente.");
    }

    public function salvaDatosReductor(){
        $this->validate([
            "equipoSerie"=>"required|min:1",
            "equipoMarca"=>"required|min:1",
            "equipoModelo"=>"required|min:1"
            ]);

        $equipo=new Equipo();
        $equipo->idTipoEquipo=$this->tipoEquipo;
        $equipo->numSerie=strtoupper($this->equipoSerie);
        $equipo->marca=strtoupper($this->equipoMarca);
        $equipo->modelo=strtoupper($this->equipoModelo);

        array_push($this->equipos,$equipo);   
        
        $this->reset(["equipoSerie","equipoMarca","equipoModelo","equipoCapacidad","tipoEquipo"]);       
        $this->open=false;
        $this->emit("alert","El ".$equipo->tipo->nombre." con serie ".$equipo->numSerie." se añadio Correctamente");
    }

    public function salvaDatosChip(){
        $this->validate([
            "equipoSerie"=>"required|min:1",           
            ]);

        $equipo=new Equipo();
        $equipo->idTipoEquipo=$this->tipoEquipo;
        $equipo->numSerie=strtoupper($this->equipoSerie);
        array_push($this->equipos,$equipo);   
        
        $this->reset(["equipoSerie","equipoMarca","equipoModelo","equipoCapacidad","tipoEquipo"]);        
        $this->open=false;
        $this->emit("alert","El ".$equipo->tipo->nombre." con serie ".$equipo->numSerie." se añadio Correctamente");
    }

    public function cuentaTipo($tipo){
        $cuenta=0;
        if(count($this->equipos)>0){
            foreach($this->equipos as $eq){
                if($eq["idTipoEquipo"] == $tipo){
                    $cuenta++;
                }
            }
        }else{
            $cuenta=0;
        }
        
        return $cuenta;
    }

    public function listaTiposDisponibles(){
        $aux=[];
        $todos=TipoEquipo::all();
        foreach($todos as $tip){
            if($tip->id==3){
                array_push($aux,array("id"=>$tip->id,"nombre"=>$tip->nombre,"estado"=>1));
            }else{
                if($this->cuentaTipo($tip->id) >= 1 ){
                    array_push($aux,array("id"=>$tip->id,"nombre"=>$tip->nombre,"estado"=>0));
                }else{
                    array_push($aux,array("id"=>$tip->id,"nombre"=>$tip->nombre,"estado"=>1));
                }
            }            
        }
        
        $this->tiposDisponibles=$aux;
        $this->tipoEquipo="";
        //return $aux;
    }   

    public function salvaEquipos(){
        $aux=[];
        if(isset($this->equipos)){
            if(count($this->equipos) > 0){
                foreach($this->equipos as $eq){
                    $equipoGuardado=$this->guardaEquipoEnBD($eq);
                    array_push($aux,$equipoGuardado);
                }
            }else{
                return null;
            }
        }
        return $aux;
    }

    public function asignaEquiposVehiculo($equipos,vehiculo $veh){
        $aux=[];
        if($equipos){
            foreach($equipos as $equipo){
                $equipoVehiculo=EquiposVehiculo::create(["idEquipo"=>$equipo["id"],"idVehiculo"=>$veh->id]);
                array_push($aux,$equipoVehiculo);
            }
        }else{
            $this->emit("CustomAlert",["titulo"=>"ERROR","mensaje"=>"No se pudo asignar equipos al vehículo","icono"=>"error"]);
        }
    }
    public function muestra(){
        $this->emit("minAlert",["titulo"=>"GOOD JOB","mensaje"=>"hola todo bien","icono"=>"success",]);       
    }
    public function guardaEquipoEnBD($modelo){
        $eq=new Equipo();
        switch ($modelo["idTipoEquipo"]) {
            case 1:
                $eq->idTipoEquipo=$modelo["idTipoEquipo"];
                $eq->numSerie=$modelo["numSerie"];
                $eq->save();
                return $eq;
                break;
            case 2:
                $eq->idTipoEquipo=$modelo["idTipoEquipo"];
                $eq->marca=$modelo["marca"];
                $eq->modelo=$modelo["modelo"];
                $eq->numSerie=$modelo["numSerie"];
                $eq->save();
                return $eq;
                break;
            case 3:
                $eq->idTipoEquipo=$modelo["idTipoEquipo"];
                $eq->numSerie=$modelo["numSerie"];
                $eq->marca=$modelo["marca"];
                $eq->capacidad=$modelo["capacidad"];
                $eq->save();
                return $eq;
                break;
            default:
                $this->emit("CustomAlert",["titulo"=>"Error","mensaje"=>"Ocurrio un error al guardar el equipo " .$modelo["numSerie"],"icono"=>"error"]);
                return null;
                break;
        }
    }


    public function certificar(){        
        $servicio=ModelServicio::find($this->serv);
        $v=$this->validaVehiculo();
        $e=$this->validaEquipos();
        $hoja=$this->procesaFormato($this->numSugerido);
        if($v && $e && $hoja){
            $cert=Certificacion::create([
                "idVehiculo"=>$this->vehiculoServicio->id,
                "idTaller"=>$this->taller,
                "idInspector"=>Auth::id(),
                "idServicio"=>$this->serv,
                "estado"=>1,
                "precio"=>$servicio->precio,
                "pagado"=>0,
            ]);
            $this->servicioCertificado=$cert;           
            $hoja->update(["estado"=>4]);
            $servM=ServicioMaterial::create([
                                            "idMaterial"=>$hoja->id,
                                            "idCertificacion"=>$cert->id
                                            ]);
            $this->asignaEquiposVehiculo($this->salvaEquipos(),$this->vehiculoServicio);
            $this->ruta=$this->generarRuta($cert);
            $this->rutaDes=$this->generarRutaDescarga($cert);
            $this->emit("minAlert",["titulo"=>"Buen Trabajo!","mensaje"=>"Tu certificado esta listo!","icono"=>"success",]);            
        }        
        
    }

  

    public function generarRuta(Certificacion $certificacion){        
        $ver="";
        $descargar="";
        if($certificacion){
            $tipoSer=$certificacion->Servicio->tipoServicio->id;            
            switch ($tipoSer) {
                case 1:
                    $ver= route('certificadoInicial', ['id' => $certificacion->id]);                    
                break; 
                case 2:
                    $ver= route('certificado', ['id' => $certificacion->id]);
                break;                
                default:
                    # code...
                    break;
            }
        }

        return $ver;
    }
    public function generarRutaDescarga(Certificacion $certificacion){        
        $descargar="";
        if($certificacion){
            $tipoSer=$certificacion->Servicio->tipoServicio->id;            
            switch ($tipoSer) {
                case 1:                    
                    $descargar=route('descargarInicial', ['id' => $certificacion->id]);
                break; 
                case 2:
                    $descargar=route('descargarCertificado', ['id' => $certificacion->id]);
                break;                
                default:
                    # code...
                    break;
            }
        }

        return $descargar;
    }

    //revisa la existencia del vehiculo en nuestra base de datos y los devuelve en caso de encontrarlo
    public function buscarVehiculo(){
        
    }

    public function procesaFormato($numSerieFormato){
        if($numSerieFormato){
            $hoja=Material::where([['numSerie',$numSerieFormato],['estado',3],['idUsuario',Auth::id()]])->first();            
            if($hoja!=null){               
                return $hoja;
            }else{
                $this->emit("CustomAlert",["titulo"=>"ERROR","mensaje"=>"El número de serie ingresado no corresponde con ningún formato en su poder","icono"=>"error"]);
                return null;
            }
        } else{
            $this->emit("CustomAlert",["titulo"=>"ERROR","mensaje"=>"Ingrese un número de serie válido.","icono"=>"error"]);
            return null;
        }
    }

    public function formatoSugerido($tipo){
        $formato=Material::where([
            ["idTipoMaterial",$tipo],
            ['idUsuario',Auth::id()],
            ["estado",3]
        ])
        ->orderBy('numSerie','asc')->first();
        if(isset($formato)){
            return $formato->numSerie;
        }else{
            return null;
        }                
    }

    public function numFormatoSugerido(){
        $formato=Material::where([
            ["idTipoMaterial",1],
            ['idUsuario',Auth::id()],
            ["estado",3]
        ])
        ->orderBy('numSerie','asc')->get();

        return $formato;
        
    }

    public function validaEquipos(){
        $estado=false;
        $chips=$this->cuentaTipo(1);
        $reg=$this->cuentaTipo(2);       
        $cil=$this->cuentaTipo(3);
            if($chips>0 && $reg>0 && $cil >0){
                $this->validaVehiculo();
                $estado=true;                
            }else{
                $this->emit("CustomAlert",["titulo"=>"ERROR","mensaje"=>"Debe completar los datos de equipos para poder certificar","icono"=>"error"]);                
            }
        return $estado;
    }
    public function validaVehiculo(){
        $estado=false;
        if($this->vehiculoServicio!=null){
            //$this->validaEquipos();
            $estado=true;
        }else{
                $this->emit("CustomAlert",["titulo"=>"ERROR","mensaje"=>"Ingrese un vehículo válido para poder certificar","icono"=>"error"]);
        }
        return $estado;
    }

    public function generaPdfAnualGnv($id){
        if(Certificacion::findOrFail($id)){
            $certificacion=Certificacion::find($id);
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
            $fecha=date('d').' días del mes de '.$meses[date('m')-1].' del '.date('Y').'.';               
            $data=[
            "fecha"=>$fecha,
            "empresa"=>"MOTORGAS COMPANY S.A.",
            "carro"=>$certificacion->Vehiculo,
            "taller"=>$certificacion->Taller, 
            "hoja"=>$certificacion->Materiales->first(), 
            ];                 
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView('anualGnv',$data);        
            return $pdf->stream($id.'-'.date('d-m-Y').'-cargo.pdf');
        }else{
            return abort(404);
        }
    }
    public function descargaPdfAnualGnv($id){
        if(Certificacion::findOrFail($id)){
            $certificacion=Certificacion::find($id);
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
            $fecha=date('d').' días del mes de '.$meses[date('m')-1].' del '.date('Y').'.'; 
            $hoja=$certificacion->Materiales->where('idTipoMaterial',1)->first();              
            $data=[
            "fecha"=>$fecha,
            "empresa"=>"MOTORGAS COMPANY S.A.",
            "carro"=>$certificacion->Vehiculo,
            "taller"=>$certificacion->Taller, 
            "hoja"=>$hoja, 
            ];                 
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView('anualGnv',$data);        
            return $pdf->download($certificacion->Vehiculo->placa.'-'.$hoja->numSerie.'.pdf');
        }else{
            return abort(404);
        }
    }

    public function generaPdfInicialGnv($id){
        if(Certificacion::findOrFail($id)){
            $certificacion=Certificacion::find($id);
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
            $fecha=date('d').' días del mes de '.$meses[date('m')-1].' del '.date('Y').'.';              
            $chip=$certificacion->vehiculo->Equipos->where("idTipoEquipo",1)->first();           
            $equipos=$certificacion->vehiculo->Equipos->where("idTipoEquipo","!=",1)->sortBy("idTipoEquipo");                     
            //dd($equipos); 
            $hoja=$certificacion->Materiales->where('idTipoMaterial',1)->first();
            $data=[
            "fecha"=>$fecha,
            "empresa"=>"MOTORGAS COMPANY S.A.",
            "carro"=>$certificacion->Vehiculo,
            "taller"=>$certificacion->Taller, 
            "hoja"=>$hoja, 
            "equipos"=>$equipos,
            "chip"=>$chip,
            ];                 
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView('conversionGnv',$data);        
            //return $pdf->stream($id.'-'.date('d-m-Y').'-cargo.pdf');
            return  $pdf->stream($certificacion->Vehiculo->placa.'-'.$hoja->numSerie.'.pdf');
        }else{
            return abort(404);
        }
    }

    public function descargaPdfInicialGnv($id){
        if(Certificacion::findOrFail($id)){
            $certificacion=Certificacion::find($id);
            $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
            $fecha=date('d').' días del mes de '.$meses[date('m')-1].' del '.date('Y').'.';              
            $chip=$certificacion->vehiculo->Equipos->where("idTipoEquipo",1)->first();           
            $equipos=$certificacion->vehiculo->Equipos->where("idTipoEquipo","!=",1)->sortBy("idTipoEquipo");                     
            //dd($equipos); 
            $hoja=$certificacion->Materiales->where('idTipoMaterial',1)->first();
            $data=[
            "fecha"=>$fecha,
            "empresa"=>"MOTORGAS COMPANY S.A.",
            "carro"=>$certificacion->Vehiculo,
            "taller"=>$certificacion->Taller, 
            "hoja"=>$hoja, 
            "equipos"=>$equipos,
            "chip"=>$chip,
            ];                 
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView('conversionGnv',$data);        
            //return $pdf->stream($id.'-'.date('d-m-Y').'-cargo.pdf');
            return  $pdf->download($certificacion->Vehiculo->placa.'-'.$hoja->numSerie.'.pdf');
        }else{
            return abort(404);
        }
    }

   
}
