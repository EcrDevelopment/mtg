<?php

namespace App\Traits;

use App\Models\Certificacion;
use App\Models\Duplicado;
use App\Models\Expediente;
use App\Models\Imagen;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

trait pdfTrait
{

    public function guardarFichaTecnica(Certificacion $certi, Expediente $expe)
    {
        $data = $this->datosParaFichaTecnica($certi);
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('fichaTecnicaGnv', $data);
        $archivo = $pdf->download('FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie . '.pdf')->getOriginalContent();
        //$nombre = $expe->placa . '-doc' . (rand()) . '-' . $expe->certificado;
        Storage::put('public/expedientes/' . 'FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie . '.pdf', $archivo);
        Imagen::create([
            'nombre' => 'FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie,
            'ruta' => 'public/expedientes/' . 'FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie . '.pdf',
            'extension' => 'pdf',
            'Expediente_idExpediente' => $expe->id,
        ]);
    }

    public function guardarFichaTecnicaGlp(Certificacion $certi, Expediente $expe)
    {
        $certificacion = $certi;
        $meses = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
            "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );
        $equipos = $certificacion->vehiculo->Equipos->where("idTipoEquipo", ">", 3)->sortBy("idTipoEquipo");
        $hoja = $certificacion->Materiales->where('idTipoMaterial', 3)->first();
        $cargaUtil = $this->calculaCargaUtil($certificacion->Vehiculo->pesoBruto, $certificacion->Vehiculo->pesoNeto);
        $fechaCert = $certificacion->created_at;
        $fec = $fechaCert->format("d/m/Y");
        $data = [
            "fecha" => $fec,
            "empresa" => "MOTORGAS COMPANY S.A.",
            "carro" => $certificacion->Vehiculo,
            "taller" => $certificacion->Taller,
            "servicio" => $certificacion->Servicio,
            "cargaUtil" => $cargaUtil,
            "hoja" => $hoja,
            "numHoja" => $this->completarConCeros($hoja->numSerie),
            "equipos" => $equipos,
        ];
        //$data = $this->datosParaFichaTecnica($certi);
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('fichaTecnicaGlp', $data);
        $archivo = $pdf->download('FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie . '-glp.pdf')->getOriginalContent();
        //$nombre = $expe->placa . '-doc' . (rand()) . '-' . $expe->certificado;
        Storage::put('public/expedientes/' . 'FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie . '-glp.pdf', $archivo);
        Imagen::create([
            'nombre' => 'FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie,
            'ruta' => 'public/expedientes/' . 'FT-' . $data['carro']->placa . '-' . $data['hoja']->numSerie . '-glp.pdf',
            'extension' => 'pdf',
            'Expediente_idExpediente' => $expe->id,
        ]);
    }

    public function datosParaFichaTecnica(Certificacion $certificacion)
    {
        $chip = $certificacion->vehiculo->Equipos->where("idTipoEquipo", 1)->first();
        $equipos = $certificacion->vehiculo->Equipos->where("idTipoEquipo", "!=", 1)->sortBy("idTipoEquipo");
        //dd($equipos);
        $hoja = $certificacion->Materiales->where('idTipoMaterial', 1)->first();
        $fechaCert = $certificacion->created_at;
        $fec = $fechaCert->format("d/m/Y");
        $data = [
            "certificacion" => $certificacion,
            "fecha" => $fec,
            "empresa" => "MOTORGAS COMPANY S.A.",
            "carro" => $certificacion->Vehiculo,
            "taller" => $certificacion->Taller,
            "servicio" => $certificacion->Servicio,
            "hoja" => $hoja,
            "equipos" => $equipos,
            "chip" => $chip,
        ];

        return $data;
    }

    public function guardaCertificado(Certificacion $certi, Expediente $expe)
    {
        switch ($certi->Servicio->tipoServicio->id) {
            case 1: //tipo servicio = inicial gnv
                $this->guardarPdfInicialGnv($certi, $expe);
                break;
            case 2: //tipo servicio = anual gnv
                $this->guardarPdfAnualGnv($certi, $expe);
                break;
            case 3: //tipo servicio = inicial glp
                $this->guardarPdfInicialGlp($certi, $expe);
                break;
            case 4: //tipo servicio = anual glp
                $this->guardarPdfAnualGlp($certi, $expe);
                break;

            case 8: //tipo servicio = duplicado gnv

                break;

            default:

                break;
        }
    }


    public function devuelveDatoParseado($num)
    {
        $str = (string) $num;
        if (substr($num, -1) != 0) {
            return rtrim($num);
        } else {
            return  bcdiv($num, '1', 2);
        }
    }

    public function guardarPdfAnualGnv(Certificacion $certificacion, Expediente $expe)
    {

        $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        $fechaCert = $certificacion->created_at;
        $fecha = $fechaCert->format('d') . ' días del mes de ' . $meses[$fechaCert->format('m') - 1] . ' del ' . $fechaCert->format('Y') . '.';
        $hoja = $certificacion->Materiales->where('idTipoMaterial', 1)->first();
        $data = [
            "fecha" => $fecha,
            "empresa" => "MOTORGAS COMPANY S.A.",
            "carro" => $certificacion->Vehiculo,
            "taller" => $certificacion->Taller,
            "hoja" => $hoja,
            "fechaCert" => $fechaCert,
            "largo" => $this->devuelveDatoParseado($certificacion->Vehiculo->largo),
            "ancho" => $this->devuelveDatoParseado($certificacion->Vehiculo->ancho),
            "altura" => $this->devuelveDatoParseado($certificacion->Vehiculo->altura),
        ];
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('anualGnv', $data);
        $archivo =  $pdf->download($certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-anual.pdf')->getOriginalContent();
        Storage::put('public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-anual.pdf', $archivo);
        Imagen::create([
            'nombre' => $certificacion->Vehiculo->placa . '-' . $hoja->numSerie,
            'ruta' => 'public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-anual.pdf',
            'extension' => 'pdf',
            'Expediente_idExpediente' => $expe->id,
        ]);
    }

    public function guardarPdfInicialGnv(Certificacion $certificacion, Expediente $expe)
    {
        //para que no te hagas tanto problema para pruebas ponte con tu vista comenta es otro mano y ya
        $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        $fechaCert = $certificacion->created_at;
        $fecha = $fechaCert->format('d') . ' días del mes de ' . $meses[$fechaCert->format('m') - 1] . ' del ' . $fechaCert->format('Y') . '.';
        $chip = $certificacion->vehiculo->Equipos->where("idTipoEquipo", 1)->first();
        $equipos = $certificacion->vehiculo->Equipos->where("idTipoEquipo", "!=", 1)->sortBy("idTipoEquipo");
        $hoja = $certificacion->Materiales->where('idTipoMaterial', 1)->first();
        $urlDelDocumento = 'www.motorgasperu.com'.route('verPdf', $certificacion->id, false);
        $qrCode = QrCode::size(70)->generate($urlDelDocumento);
        $data = [
            "fecha" => $fecha,
            "empresa" => "MOTORGAS COMPANY S.A.",
            "carro" => $certificacion->Vehiculo,
            "taller" => $certificacion->Taller,
            "hoja" => $hoja,
            "equipos" => $equipos,
            "chip" => $chip,
            "fechaCert" => $fechaCert,
            "pesos" => $certificacion->calculaPesos,
            "largo" => $this->devuelveDatoParseado($certificacion->Vehiculo->largo),
            "ancho" => $this->devuelveDatoParseado($certificacion->Vehiculo->ancho),
            "altura" => $this->devuelveDatoParseado($certificacion->Vehiculo->altura),
            "qrCode"=> $qrCode,
        ];
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('conversionGnv', $data);
        $archivo = $pdf->download($certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-inicial.pdf')->getOriginalContent();
        Storage::put('public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-inicial.pdf', $archivo);
        Imagen::create([
            'nombre' => $certificacion->Vehiculo->placa . '-' . $hoja->numSerie,
            'ruta' => 'public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-inicial.pdf',
            'extension' => 'pdf',
            'Expediente_idExpediente' => $expe->id,
        ]);
    }

    public function guardarPdfAnualGlp(Certificacion $certificacion, Expediente $expe)
    {
        $meses = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );
        $fechaCert = $certificacion->created_at;
        $fecha = $fechaCert->format('d') . ' días del mes de ' . $meses[$fechaCert->format('m') - 1] . ' del ' . $fechaCert->format('Y') . '.';
        $hoja = $certificacion->Materiales->where('idTipoMaterial', 3)->first();
        $equipos = $certificacion->Vehiculo->Equipos->where("idTipoEquipo", ">", 3)->sortBy("idTipoEquipo");
        $cargaUtil = $this->calculaCargaUtil($certificacion->Vehiculo->pesoBruto, $certificacion->Vehiculo->pesoNeto);
        $data = [
            "fecha" => $fecha,
            "cargaUtil" => $cargaUtil,
            "empresa" => "MOTORGAS COMPANY S.A.",
            "carro" => $certificacion->Vehiculo,
            "taller" => $certificacion->Taller,
            "hoja" => $hoja,
            "numHoja" => $this->completarConCeros($hoja->numSerie),
            "fechaCert" => $fechaCert,
            "equipos" => $equipos,
            "largo" => $this->devuelveDatoParseado($certificacion->Vehiculo->largo),
            "ancho" => $this->devuelveDatoParseado($certificacion->Vehiculo->ancho),
            "altura" => $this->devuelveDatoParseado($certificacion->Vehiculo->altura),
        ];
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('anualGlp', $data);
        $archivo =  $pdf->download($certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-anual-glp.pdf')->getOriginalContent();
        Storage::put('public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-anual-glp.pdf', $archivo);
        Imagen::create([
            'nombre' => $certificacion->Vehiculo->placa . '-' . $hoja->numSerie,
            'ruta' => 'public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-anual-glp.pdf',
            'extension' => 'pdf',
            'Expediente_idExpediente' => $expe->id,
        ]);
    }

    public function guardarPdfInicialGlp(Certificacion $certificacion, Expediente $expe)
    {
        $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        $fechaCert = $certificacion->created_at;
        $fecha = $fechaCert->format('d') . ' días del mes de ' . $meses[$fechaCert->format('m') - 1] . ' del ' . $fechaCert->format('Y') . '.';
        $hoja = $certificacion->Materiales->where('idTipoMaterial', 3)->first();
        $equipos = $certificacion->Vehiculo->Equipos->where("idTipoEquipo", ">", 3)->sortBy("idTipoEquipo");
        $cargaUtil = $this->calculaCargaUtil($certificacion->Vehiculo->pesoBruto, $certificacion->Vehiculo->pesoNeto);
        $data = [
            "fecha" => $fecha,
            "cargaUtil" => $cargaUtil,
            "empresa" => "MOTORGAS COMPANY S.A.",
            "carro" => $certificacion->Vehiculo,
            "taller" => $certificacion->Taller,
            "hoja" => $hoja,
            "numHoja" => $this->completarConCeros($hoja->numSerie),
            "fechaCert" => $fechaCert,
            "equipos" => $equipos,
            "largo" => $this->devuelveDatoParseado($certificacion->Vehiculo->largo),
            "ancho" => $this->devuelveDatoParseado($certificacion->Vehiculo->ancho),
            "altura" => $this->devuelveDatoParseado($certificacion->Vehiculo->altura),
        ];
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('conversionGlp', $data);
        $archivo = $pdf->download($certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-inicial-glp.pdf')->getOriginalContent();
        Storage::put('public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-inicial-glp.pdf', $archivo);
        Imagen::create([
            'nombre' => $certificacion->Vehiculo->placa . '-' . $hoja->numSerie,
            'ruta' => 'public/expedientes/' . $certificacion->Vehiculo->placa . '-' . $hoja->numSerie . '-inicial-glp.pdf',
            'extension' => 'pdf',
            'Expediente_idExpediente' => $expe->id,
        ]);
    }

    public function calculaCargaUtil($pesoBruto, $pesoNeto)
    {
        if ($pesoBruto && $pesoNeto) {
            $cu = $pesoBruto - $pesoNeto;
            return ($pesoBruto - $pesoNeto);
        }
    }

    function completarConCeros($numero, $longitudDeseada = 7)
    {
        // Convierte el número a una cadena y completa con ceros a la izquierda
        return str_pad((string)$numero, $longitudDeseada, '0', STR_PAD_LEFT);
    }
}
