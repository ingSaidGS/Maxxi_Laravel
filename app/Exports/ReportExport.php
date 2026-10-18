<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport
{
    public function __construct(
        protected Carbon $from,
        protected Carbon $to,
        protected array $data
    ) {}

    public function generate($spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        $row = 1;

        // Encabezado
        $sheet->setCellValue('A'.$row, 'Reporte del '.$this->from->format('d/m/Y').' al '.$this->to->format('d/m/Y'));
        $this->styleTitle($sheet, 'A'.$row.':F'.$row);
        $sheet->mergeCells('A'.$row.':F'.$row);
        $row++;

        $sheet->setCellValue('A'.$row, 'Generado el '.now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A'.$row.':F'.$row);
        $this->styleSubtitle($sheet, 'A'.$row);
        $row += 2;

        // 1. RESUMEN ECONÓMICO
        $this->addSection($sheet, $row, 'RESUMEN ECONÓMICO', 2);
        $row++;
        $economic = $this->data['economic'];
        $start = $row;
        $this->addHeader($sheet, $row, ['Concepto', 'Valor'], [Alignment::HORIZONTAL_LEFT, Alignment::HORIZONTAL_RIGHT]);
        $row++;
        $this->addRowNum($sheet, $row++, ['Ventas netas', $economic['net']]);
        $this->addRowNum($sheet, $row++, ['Ventas brutas', $economic['gross']]);
        $this->addRowNum($sheet, $row++, ['Descuentos', $economic['discount']]);
        $this->addRowNum($sheet, $row++, ['Efectivo', $economic['cash']]);
        $this->addRowNum($sheet, $row++, ['QR', $economic['qr']]);
        $this->addRowNum($sheet, $row++, ['Fiado', $economic['debt']]);
        $this->addRowNum($sheet, $row++, ['Inversión (costo)', $economic['cost']]);
        $this->addBoldRow($sheet, $row++, ['Ganancia bruta', $economic['profit']]);
        $this->addRowNum($sheet, $row++, ['Margen (%)', $economic['margin']]);
        $this->addRowNum($sheet, $row++, ['Inversión sobre ventas (%)', $economic['cost_share']]);
        $this->addRowNum($sheet, $row++, ['N° de ventas', $economic['sales_count'], false, false]);
        $this->addRowNum($sheet, $row++, ['Ticket promedio', $economic['avg_ticket']]);
        $this->applyBorders($sheet, 'A'.$start.':B'.($row - 1));
        $row += 2;

        // 2. VENTAS POR DÍA
        $this->addSection($sheet, $row, 'VENTAS POR DÍA', 6);
        $row++;
        $start = $row;
        $this->addHeader($sheet, $row, ['Fecha', 'Ventas', 'Netas', 'Efectivo', 'QR', 'Fiado'], [
            Alignment::HORIZONTAL_LEFT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
        ]);
        $row++;
        $byDay = $this->data['byDay'] ?? collect();
        if ($byDay->isEmpty()) {
            $sheet->setCellValue('A'.$row, 'Sin ventas en el rango.');
            $sheet->mergeCells('A'.$row.':F'.$row);
            $this->styleMuted($sheet, 'A'.$row);
            $row++;
        } else {
            $i = 0;
            foreach ($byDay as $item) {
                $sheet->setCellValue('A'.$row, Carbon::parse($item->day)->format('d/m/Y'));
                $sheet->setCellValue('B'.$row, (int) $item->sales_count);
                $sheet->setCellValue('C'.$row, (float) $item->net);
                $sheet->setCellValue('D'.$row, (float) $item->cash);
                $sheet->setCellValue('E'.$row, (float) $item->qr);
                $sheet->setCellValue('F'.$row, (float) $item->debt);
                $this->formatQty($sheet, 'B'.$row);
                $this->formatMoney($sheet, 'C'.$row.':F'.$row);
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                if ($i % 2 === 1) {
                    $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFAF7ED');
                }
                $row++;
                $i++;
            }
        }
        $this->applyBorders($sheet, 'A'.$start.':F'.($row - 1));
        $row += 2;

        // 3. TOP PRODUCTOS
        $this->addSection($sheet, $row, 'TOP PRODUCTOS VENDIDOS', 5);
        $row++;
        $start = $row;
        $this->addHeader($sheet, $row, ['#', 'Producto', 'Cantidad (unidad base)', 'Unidad', 'Ingresos'], [
            Alignment::HORIZONTAL_LEFT,
            Alignment::HORIZONTAL_LEFT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_LEFT,
            Alignment::HORIZONTAL_RIGHT,
        ]);
        $row++;
        $top = $this->data['topProducts'] ?? collect();
        if ($top->isEmpty()) {
            $sheet->setCellValue('A'.$row, 'Sin productos vendidos en el rango.');
            $sheet->mergeCells('A'.$row.':E'.$row);
            $this->styleMuted($sheet, 'A'.$row);
            $row++;
        } else {
            $i = 0;
            foreach ($top as $item) {
                $sheet->setCellValue('A'.$row, $i + 1);
                $sheet->setCellValue('B'.$row, $item->product_name);
                $sheet->setCellValue('C'.$row, (int) $item->quantity);
                $sheet->setCellValue('D'.$row, $item->unit_symbol ?? '');
                $sheet->setCellValue('E'.$row, (float) $item->revenue);
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $this->formatQty($sheet, 'C'.$row);
                $this->formatMoney($sheet, 'E'.$row);
                if ($i % 2 === 1) {
                    $sheet->getStyle('A'.$row.':E'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFAF7ED');
                }
                $row++;
                $i++;
            }
        }
        $this->applyBorders($sheet, 'A'.$start.':E'.($row - 1));
        $row += 2;

        // 4. VENTAS POR USUARIO
        $this->addSection($sheet, $row, 'VENTAS POR USUARIO', 6);
        $row++;
        $start = $row;
        $this->addHeader($sheet, $row, ['Usuario', 'Ventas', 'Netas', 'Efectivo', 'QR', 'Fiado'], [
            Alignment::HORIZONTAL_LEFT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
            Alignment::HORIZONTAL_RIGHT,
        ]);
        $row++;
        $byUser = $this->data['byUser'] ?? collect();
        if ($byUser->isEmpty()) {
            $sheet->setCellValue('A'.$row, 'Sin ventas por usuario en el rango.');
            $sheet->mergeCells('A'.$row.':F'.$row);
            $this->styleMuted($sheet, 'A'.$row);
            $row++;
        } else {
            $i = 0;
            foreach ($byUser as $item) {
                $sheet->setCellValue('A'.$row, $item->user_name);
                $sheet->setCellValue('B'.$row, (int) $item->sales_count);
                $sheet->setCellValue('C'.$row, (float) $item->net);
                $sheet->setCellValue('D'.$row, (float) $item->cash);
                $sheet->setCellValue('E'.$row, (float) $item->qr);
                $sheet->setCellValue('F'.$row, (float) $item->debt);
                $this->formatQty($sheet, 'B'.$row);
                $this->formatMoney($sheet, 'C'.$row.':F'.$row);
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                if ($i % 2 === 1) {
                    $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFAF7ED');
                }
                $row++;
                $i++;
            }
        }
        $this->applyBorders($sheet, 'A'.$start.':F'.($row - 1));

        // Auto-ajuste
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function styleTitle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF1A2E45');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    protected function styleSubtitle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setSize(10)->getColor()->setARGB('FF475467');
    }

    protected function styleMuted(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->getColor()->setARGB('FF475467');
    }

    protected function addSection(Worksheet $sheet, int &$row, string $title, int $cols): void
    {
        $end = Coordinate::stringFromColumnIndex($cols);
        $sheet->setCellValue('A'.$row, $title);
        $sheet->mergeCells('A'.$row.':'.$end.$row);
        $sheet->getStyle('A'.$row.':'.$end.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF87613');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
    }

    protected function addHeader(Worksheet $sheet, int $row, array $headers, array $hAlign): void
    {
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.$row, $h);
            $sheet->getStyle($col.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1A2E45');
            $sheet->getStyle($col.$row)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col.$row)->getAlignment()->setHorizontal($hAlign[$i] ?? Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        }
    }

    protected function addRowNum(Worksheet $sheet, int $row, array $vals, bool $money = true, bool $percent = false): void
    {
        $sheet->setCellValue('A'.$row, $vals[0]);
        $sheet->setCellValue('B'.$row, $vals[1]);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        if ($money && ! $percent) {
            $this->formatMoney($sheet, 'B'.$row);
        } elseif ($percent) {
            $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            $this->formatQty($sheet, 'B'.$row);
        }
    }

    protected function addBoldRow(Worksheet $sheet, int $row, array $vals): void
    {
        $sheet->setCellValue('A'.$row, $vals[0]);
        $sheet->setCellValue('B'.$row, $vals[1]);
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $this->formatMoney($sheet, 'B'.$row);
    }

    protected function formatMoney(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.0');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    protected function formatQty(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    protected function applyBorders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('FFC9CED6'));
    }
}
