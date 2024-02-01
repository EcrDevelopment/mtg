<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteCalcularExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles, WithColumnFormatting, WithStrictNullComparison
{
    use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Reporte Calcular MTC';
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function headings(): array
    {
        return [
            'Taller',
            'Inspector',
            'Vehículo',
            'Servicio',
            'Fecha',
            'Estado',
            'Pagado',
            'Precio',
        ];
    }

    public function map($data): array
    {
        return [
            $data->taller,
            $data->nombre,
            $data->placa ?? 'EN TRAMITE',
            $data->tiposervicio,
            $data->created_at,
            $data->estado,
            $data->pagado,
            $data->precio,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H' . $sheet->getHighestRow())
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('1:1')->getFont()->setBold(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Encontrar el número de filas en el conjunto de datos
                $rowCount = count($this->data) + 2; // +2 para incluir la fila de encabezado y empezar desde 1

                // Agregar una fila vacía después de cada grupo de datos del inspector
                $inspectorActual = null;

                for ($i = 2; $i <= $rowCount; $i++) {
                    $inspectorSiguiente = $event->sheet->getCell('B' . $i)->getValue();

                    if ($inspectorActual !== null && $inspectorActual !== $inspectorSiguiente) {
                        $event->sheet->insertNewRowBefore($i, 1);
                        $rowCount++; // Incrementar el número total de filas
                        $i++; // Saltar la fila que acabamos de agregar
                    }

                    $inspectorActual = $inspectorSiguiente;
                }
            },
        ];
    }
}
