<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CargaImagenes extends Component
{
    use WithFileUploads;

    public $archivo=[];
    public $urls;

    public function mount(){
        $this->listarArchivos();
    }

    public function render()
    {
        return view('livewire.carga-imagenes');
    }

    public function procesar(){
        
        foreach ($this->archivo as $file) {
           $file->store('pruebas','do');
        }

    }

    public function listarArchivos(){

        $archivos=Storage::disk('do')->allFiles('pruebas');
        $urls=[];

        foreach ($archivos as $archivo) {
            $urls[] = Storage::disk('do')->url($archivo);
        }
    
        $this->urls = $urls;

    }
}
