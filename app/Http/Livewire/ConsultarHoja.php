<?php

namespace App\Http\Livewire;

use App\Models\TipoMaterial;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ConsultarHoja extends Component
{
    public $numSerie, $resultados;
    public $desde, $hasta;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.consultar-hoja');
    }

    public function buscar()
    {
        if (!empty($this->desde) && !empty($this->hasta)) {
            // Realizar la consulta a la base de datos con JOIN a la tabla users y tipomaterial
            $this->resultados = DB::table('material')
                ->whereBetween('numSerie', [$this->desde, $this->hasta])
                ->leftJoin('users', 'material.idUsuario', '=', 'users.id')
                ->leftJoin('tipomaterial', 'material.idTipoMaterial', '=', 'tipomaterial.id')
                ->select(
                    'material.*',
                    'users.name as nombreUsuario',
                    'tipomaterial.descripcion as descripcionTipoMaterial'
                )
                ->get();
        } else {
        }
    }
}
