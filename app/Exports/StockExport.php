<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta el reporte de stock (materia prima + productos) a Excel, con
 * su valorización, para anexos de tesis o control interno.
 */
class StockExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        protected Collection $materias,
        protected Collection $productos,
    ) {}

    public function collection()
    {
        $filas = collect();

        foreach ($this->materias as $m) {
            $filas->push((object) [
                'tipo'       => 'Materia Prima',
                'nombre'     => $m->nombre,
                'referencia' => $m->proveedor->nombre ?? '—',
                'stock'      => $m->stock_actual,
                'unidad'     => $m->unidad->abreviatura,
                'minimo'     => $m->stock_minimo,
                'costo'      => $m->costo_unitario,
                'valor'      => $m->stock_actual * $m->costo_unitario,
                'estado'     => ['agotado' => 'Agotado', 'bajo' => 'Stock bajo', 'ok' => 'Normal'][$m->nivel] ?? 'Normal',
                'reponer'    => $m->reponer ?? 0,
            ]);
        }

        foreach ($this->productos as $p) {
            $filas->push((object) [
                'tipo'       => 'Producto',
                'nombre'     => $p->nombre,
                'referencia' => $p->categoria->nombre ?? '—',
                'stock'      => $p->stock_actual,
                'unidad'     => 'uds',
                'minimo'     => $p->stock_minimo,
                'costo'      => $p->costo_produccion ?? 0,
                'valor'      => $p->stock_actual * ($p->costo_produccion ?? 0),
                'estado'     => ['agotado' => 'Agotado', 'bajo' => 'Stock bajo', 'ok' => 'Normal'][$p->nivel] ?? 'Normal',
                'reponer'    => $p->reponer ?? 0,
            ]);
        }

        return $filas;
    }

    public function headings(): array
    {
        return ['Tipo', 'Nombre', 'Proveedor / Categoría', 'Stock', 'Unidad', 'Stock Mínimo', 'Costo Unitario', 'Valor Total', 'Estado', 'Cantidad a reponer'];
    }

    public function map($fila): array
    {
        return [
            $fila->tipo,
            $fila->nombre,
            $fila->referencia,
            round($fila->stock, 2),
            $fila->unidad,
            round($fila->minimo, 2),
            round($fila->costo, 2),
            round($fila->valor, 2),
            $fila->estado,
            $fila->reponer > 0 ? round($fila->reponer, 2) : '',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 28, 'C' => 22, 'D' => 10, 'E' => 10, 'F' => 12, 'G' => 14, 'H' => 14, 'I' => 12, 'J' => 18];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A2E']]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Stock';
    }
}
