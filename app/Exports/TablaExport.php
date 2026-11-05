<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportación genérica de una tabla a Excel (cierre diario, mermas, compras…):
 * un título arriba, encabezados en negrita y columnas con ancho automático.
 */
class TablaExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    /** @param array $filas filas ya listas (arrays de valores) */
    public function __construct(
        protected string $hoja,
        protected array $encabezados,
        protected array $filas,
        protected ?string $titulo = null,
    ) {}

    public function array(): array { return $this->filas; }

    public function headings(): array
    {
        return $this->titulo ? [[$this->titulo], [], $this->encabezados] : [$this->encabezados];
    }

    public function title(): string { return mb_substr($this->hoja, 0, 31); }

    public function styles(Worksheet $sheet)
    {
        $fila = $this->titulo ? 3 : 1;
        $estilos = [$fila => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A2E']]]];
        if ($this->titulo) $estilos[1] = ['font' => ['bold' => true, 'size' => 13]];
        return $estilos;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $fila = $this->titulo ? 3 : 1;
            $e->sheet->getDelegate()->freezePane('A' . ($fila + 1));
        }];
    }
}
