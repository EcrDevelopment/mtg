<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class CargaFotos extends Component
{
    use WithFileUploads;

    public $imagenes=[];

    public function render()
    {
        return view('livewire.carga-fotos');
    }

    public function muestraData(){
        //dd($this->imagenes);
    }
}
