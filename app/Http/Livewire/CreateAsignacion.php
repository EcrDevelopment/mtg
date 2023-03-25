<?php

namespace App\Http\Livewire;

use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateAsignacion extends Component
{

    public $open=false;  

    public $inspectores,$inspector,$tiposMateriales,$cantidad,$nombre,$motivo,$nombreTipo,$guia,$numInicio,$numFinal;
    public $guias;
    public $tipoM=0;
    public $stocks=[];   

    protected $rules=[               
        "tipoM"=>"required|numeric",
        "motivo"=>"required|min:3","cantidad"=>"required",      
    ];
   

    public function listaStock(){
        $materiales=TipoMaterial::all();
        foreach($materiales as $key=>$material){
            $lista=Material::where([
                                    ['estado',1],
                                    ['idTipoMaterial',$material->id]
                                    ])
                            ->get();
            $this->stocks+=[$material->descripcion=>count($lista)];
        }
    }

    public function mount(){
        $this->listaStock();
        $this->inspectores=User::role(['inspector','supervisor'])        
                                ->orderBy('name')
                                ->get();
        $this->tiposMateriales=TipoMaterial::all()->sortBy("descripcion");
        //$this->guias= new Collection();     
    }

    public function render(){        
        return view('livewire.create-asignacion');
    }

    
    public function updated($propertyName){        
        switch ($this->tipoM) {
            case 1:                
                $this->guias=json_decode(Material::stockPorGruposGnv(),true);
                if($this->guia){
                    $cant=Material::where([
                        ['estado',1],
                        ['idTipoMaterial',$this->tipoM],
                        ['grupo',$this->guia],
                        ])->count();
                    //dd($cant);
                    //$this->reset(["numInicio"]);                    
                    if (array_key_exists("cantidad",$this->rules)){
                        $this->rules["cantidad"]="required|numeric|min:1|max:".$cant;
                    }else{
                        $this->rules+=["cantidad"=>'required|numeric|min:1|max:'.$cant];
                    }
                }
            break;
            case 2:
                $this->rules+=["cantidad"=>'required|numeric|min:1|max:'.$this->stocks["CHIP"]];
                break;
            case 3:
                $this->guias=json_decode(Material::stockPorGruposGlp(),true);
                if($this->guia){
                    $cant=Material::where([
                        ['estado',1],
                        ['idTipoMaterial',$this->tipoM],
                        ['grupo',$this->guia],
                        ])->count();
                    //dd($cant);
                    //$this->reset(["numInicio"]);                    
                    //$this->rules+=["cantidad"=>'required|numeric|min:1|max:'.$cant];
                    if (array_key_exists("cantidad",$this->rules)){
                        $this->rules["cantidad"]="required|numeric|min:1|max:".$cant;
                    }else{
                        $this->rules+=["cantidad"=>'required|numeric|min:1|max:'.$cant];
                    }
                }                   
            break;
            default:
               $this->guias=new Collection();                         
            break;
        }     

           
        if($propertyName=="numInicio" && $this->cantidad>0){

            if($this->numInicio){               
                $this->numFinal=$this->numInicio+($this->cantidad-1);                
            }
        }
        if($propertyName=="cantidad" && $this->numInicio>0){
            if($this->cantidad){               
                $this->numFinal=$this->numInicio+($this->cantidad-1);
            }else{
                $this->numFinal=0;
            }
        }         
       
    $this->validateOnly($propertyName);    
    }
    /*
    public function updatedTipoM($value){
       
        switch ($value!=null){
            case 1:                
                $this->guias=Material::stockPorGruposGnv();                
            break;
            case 2:
                $this->rules+=["cantidad"=>'required|numeric|min:1|max:'.$this->stocks["CHIP"]];
                break;
            case 3:
                $this->guias=Material::stockPorGruposGlp();                
            break;
            default:
                $this->guias= new Collection();                    
            break;
        }
        
    }

    public function updatedGrupo($value){
       
        if($value!=null){
            $cant=Material::where([
                ['estado',1],
                ['idTipoMaterial',$this->tipoM],
                ['grupo',$value],
                ])->count();                               
            $this->rules+=["cantidad"=>'required|numeric|min:1|max:'.$cant];
        }
       
    }
*/
    public function updatedCantidad(){
        //Muestra el formato con el numeroSerie mas bajo segun el Tipo de Material y Grupo Seleccionado
        if($this->validateOnly("cantidad")){
            $num=Material::where([
                ['estado',1],
                ['idTipoMaterial',$this->tipoM],
                ['grupo',$this->guia],
                ])->orderBy("numSerie","asc")->min("numSerie");;
                $this->numInicio=$num;
        }
    }    


    public function creaColeccion($inicio,$fin){
        $cole= new Collection();
        for ($i=$inicio; $i <=$fin; $i++) { 
            $cole->push($i);
        }
        return $cole;
    }

    public function validaSeries(){
        $result= new Collection();        
        if($this->tipoM==1 || $this->tipoM==3){
            if($this->numInicio && $this->guia){
                $series=$this->creaColeccion($this->numInicio,$this->numFinal);
                $mat=Material::where([['idTipoMaterial',$this->tipoM],['grupo',$this->guia],["estado",1]])->pluck('numSerie');
                $result=$mat->intersect($series);
            }
        }           
        return $result;
    }

    public function addArticulo(){
        
        $rule=[];

        switch ($this->tipoM) {
            case 1:
                $rule=[ "guia"=>'required',
                        "numInicio"=>'required',
                        "numFinal"=>'required',
                        ];
            break;

            case 2:
               //$rule=["cantidad"=>'required|numeric|min:1|max:'.$this->stocks["CHIP"]];
            break;

            case 3:
                $rule=[ "guia"=>'required',
                "numInicio"=>'required|number|min:1',
                "numFinal"=>'required|number|min:1',
                ];
            break;

            default:
                $rule=["cantidad"=>'required|numeric|min:1'];
            break;
        }
        $this->validate($rule);  

        $this->emit("minAlert",["titulo"=>"BUEN TRABAJO!","mensaje"=>"El articulo se añadio Correctamente","icono"=>"success"]);

        $temp=$this->validaSeries();
        if($temp->count()>0){

           // $this->emit("minAlert",["titulo"=>"TODO OK","mensaje"=>"BIEN HECHO ".$temp->count(),"icono"=>"success"]); 
            $articulo= array("tipo"=>$this->tipoM,"nombreTipo"=>$this->nombreTipo,"cantidad"=>$this->cantidad,"inicio"=>$this->numInicio,"final"=>$this->numFinal,"motivo"=>$this->motivo);
            $this->emit('agregarArticulo',$articulo);
            $this->reset(['tipoM','motivo','cantidad','guia','numInicio','numFinal']);
            $this->open=false;
            $this->emit("minAlert",["titulo"=>"BUEN TRABAJO!","mensaje"=>"El articulo se añadio Correctamente","icono"=>"success"]);
            //$this->reset(["grupo"]);
        }else{
            $this->emit("minAlert",["titulo"=>"ERROR","mensaje"=>"Las series ingresadas no pertenecen al grupo seleccionado o no existen ","icono"=>"error"]); 
            $this->reset(['tipoM','motivo','cantidad','guia','numInicio','numFinal']);

        }

    }      

    public function updatedOpen(){        
        $this->reset(['tipoM','motivo','cantidad','stocks','numInicio','numFinal']);
        $this->listaStock();
    }

    
}
