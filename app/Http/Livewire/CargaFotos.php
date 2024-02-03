<?php

namespace App\Http\Livewire;

use App\Models\Archivo;
use App\Models\Imagen;
use Livewire\Component;
use Livewire\WithFileUploads;
use Intervention\Image\Facades\Image;

class CargaFotos extends Component
{
    use WithFileUploads;

    public $imagenes = [];

    public function render()
    {
        return view('livewire.carga-fotos');
    }

    public function muestraData()
    {

        //dd($this->imagenes);
        $this->validate([
            'imagenes' => 'required|array|min:1',
            'imagenes.*' => 'image|max:2048', // Tamaño máximo de 2MB
        ]);

        $imagenesGuardadas = [];


        foreach ($this->imagenes as $imagen) {
            $rutaImagen = $imagen->store('public/prueba');

            $nuevaImagen = Imagen::create([
                'nombre' => pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME),
                'ruta' => $rutaImagen,
                'extension' => $imagen->getClientOriginalExtension(),
                //'idDocReferenciado' => null,
            ]);
            // Reducir el tamaño de la imagen con Intervention Image (si es necesario)
            Image::make(storage_path("app/{$rutaImagen}"))
                ->resize(300, 200) 
                ->save(storage_path("app/{$rutaImagen}"));
            
            $imagenesGuardadas[] = $nuevaImagen;  // Agregar la imagen recién creada al array
        }
        
        $this->imagenes = [];
        session()->flash('success', 'Imágenes cargadas y procesadas correctamente.');
        return $imagenesGuardadas;
    }
}
