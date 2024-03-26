<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Certificacion;
use Illuminate\Support\Facades\Auth;

class ServicioDesmonte extends Component
{
    public $desmonte,$placa,$estado="esperando", $taller , $servicio;

    protected $rules=[
        "placa"=>"required|min:6|max:7"
    ];

    public function render()
    {
        return view('livewire.servicio-desmonte');
    }

    public function desmonte(){
        $this->validate();

        //dd($this->servicio);
        $certificar = Certificacion::certificarDesmonte($this->taller,  $this->servicio,  Auth::user(), $this->placa);
        
        if($certificar){
            $this->estado="ChipConsumido";                   
            $this->emit("minAlert", ["titulo" => "¡BUEN TRABAJO!", "mensaje" => "Se realizo correctamente", "icono" => "success"]);
        }else{
            $this->emit("minAlert", ["titulo" => "AVISO DEL SISTEMA", "mensaje" => "Ocurrio un error", "icono" => "warning"]);
        }
            
    }
}
