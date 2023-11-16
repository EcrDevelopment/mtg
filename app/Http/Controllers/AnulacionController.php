<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Anulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnulacionController extends Controller
{
    
    public function marcarAnulacion($idNotificacion) {
        $notification = Auth()->user()->notifications->find($idNotificacion);
    
        if ($notification && isset($notification->data['idAnulacion'])) {
            $anuId = $notification->data['idAnulacion'];
            $idUsuario = $notification->data['idInspector'];
            $idServicio = $notification->data['idServicio'];
    
            Auth()->user()->unreadNotifications->when($idNotificacion, function ($query) use ($idNotificacion) {
                return $query->where('id', $idNotificacion);
            })->markAsRead();
    
            return redirect()->route('vistaSolicitudAnul', ['anuId' => $anuId, 'cerId' => $idServicio, 'userId'=>$idUsuario] );
        }
    }


    public function marcarTodasLasNotificaciones(){
        Auth()->user()->unreadNotifications->markAsRead();
        redirect()->route('anulacion');
    }
}
