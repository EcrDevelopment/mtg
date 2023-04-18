<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\Expediente;
use App\Models\User;

class ResumenExpedientes extends Component
{
    

    public $expedientes,$porRevisar,$observados,$aprobados,$desaprobados,$rol,$total,$mensaje;
    public array $dataset = [];
    public array $labels = [];  
    public array $colores = [];  

    protected $listeners = [
        'enviaDatos',
    ];
    

    public function mount(){     
      
       $this->cargaDatos();
       $this->formateaChart();

    }


    public function cargaDatos(){
        $user=User::find(Auth::id());
        if($user->hasRole('inspector')){
            $this->expedientes=Expediente::where('usuario_idusuario',Auth::id())->get();
            foreach($this->expedientes as $item){
                switch($item->estado){
                    case 1:
                        $this->porRevisar++;                    
                    break;
                    case 2:
                        $this->observados++;                    
                    break;
                    case 3:
                        $this->aprobados++;                    
                    break;
                    case 4:
                        $this->desaprobados++;                    
                    break;
                }
            }
            $this->total=Expediente::where('usuario_idusuario',Auth::id())->count();
        } elseif($user->hasRole('administrador')){
            $this->expedientes=Expediente::all();
            foreach($this->expedientes as $item){
                switch($item->estado){
                    case 1:
                        $this->porRevisar++;                    
                    break;
                    case 2:
                        $this->observados++;                    
                    break;
                    case 3:
                        $this->aprobados++;                    
                    break;
                    case 4:
                        $this->desaprobados++;                    
                    break;
                }
            }
            $this->total=Expediente::All()->count();
                      
        }      
    }

    public function formateaChart(){
        $this->mensaje='Total ('.$this->total.')';
        $this->labels = ['Por revisar','Observados','Desaprobados','Aprobados'];
        $this->colores= [
            'rgba(251, 146, 60, 0.8)',
            'rgba(96, 165, 250, 0.8)',
            'rgba(248, 20, 0, 0.8)',
            'rgba(163, 230, 53, 0.8)',         
        ];
        $this->dataset = [
            [
                'label' => $this->mensaje,               
                'data' =>array($this->porRevisar,$this->observados,$this->desaprobados,$this->aprobados),
                'backgroundColor'=>$this->colores,
            ],            
        ];  
    }

    public function enviaDatos()
    {     
        $this->emit('updateChart', [
            'datasets' => $this->dataset,
            'labels' => $this->labels,            
        ]);
    }

    public function render()
    {
       return view('livewire.resumen-expedientes');
    }
}
