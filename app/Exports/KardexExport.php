<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta la ficha kardex (cronológica, con saldo) de un insumo o
 * producto puntual a Excel.
 *
 * Estructura de la hoja:
 *   fila 1: título del kardex
 *   fila 2: encabezados
 *   fila 3: saldo inicial del periodo
 *   fila 4+: movimientos (entradas, salidas y ajustes ya normalizados)
 */
class KardexExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        protected $item,
        protected Collection $movimientos,
        protected string $unidad,
        protected float $saldoInicial = 0,
    ) {}

    public function array(): array
    {
        $filas = [
            ["Kardex: {$this->item->nombre}", '', '', '', '', '', '', ''],
            ['Fecha', 'Tipo', 'Motivo', 'Entrada', 'Salida', 'Saldo', 'Usuario', 'Observación'],
            ['', 'Saldo inicial', '', '', '', round($this->saldoInicial, 3) . ' ' . $this->unidad, '', ''],
        ];

        foreach ($this->movimientos as $mov) {
            $filas[] = [
                $mov->created_at->format('d/m/Y H:i'),
                $mov->es_ajuste ? 'Ajuste' : ucfirst($mov->tipo),
                ucfirst(str_replace('_', ' ', $mov->motivo)),
                $mov->entrada > 0 ? round($mov->entrada, 3) : '',
                $mov->salida  > 0 ? round($mov->salida, 3)  : '',
                round($mov->stock_despues, 3) . ' ' . $this->unidad,
                $mov->usuario->apodo ?? $mov->usuario->nombre ?? '—',
                $mov->observacion ?? '',
            ];
        }

        return $filas;
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 14, 'C' => 18, 'D' => 11, 'E' => 11, 'F' => 16, 'G' => 18, 'H' => 40];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A2E']]],
            3 => ['font' => ['bold' => true, 'italic' => true],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F4F1EE']]],
        ];
    }

    public function title(): string
    {
        return 'Kardex';
    }
}
