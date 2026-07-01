<?php

namespace App\Http\Controllers;

use App\Imports\ProductsImport;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ImportController extends Controller
{
    // ─── Página de importación ────────────────────────────────────────────────

    public function showProducts(): Response
    {
        return Inertia::render('Imports/Products', [
            'units'      => UnitOfMeasure::orderBy('abbreviation')->get(['id', 'name', 'abbreviation']),
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ─── Procesar importación ─────────────────────────────────────────────────

    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'Seleccione un archivo Excel.',
            'file.mimes'    => 'El archivo debe ser Excel (.xlsx o .xls).',
            'file.max'      => 'El archivo no puede superar 10 MB.',
        ]);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));

            session()->flash('import_result', [
                'created' => $import->created,
                'updated' => $import->updated,
                'errors'  => $import->errors,
                'total'   => $import->created + $import->updated,
            ]);

            return redirect()->route('imports.products');

        } catch (\Exception $e) {
            return back()->withErrors([
                'file' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ]);
        }
    }

    // ─── Descargar plantilla Excel ────────────────────────────────────────────

    public function productsTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // ─── Cabeceras ────────────────────────────────────────────────────
        $headers = [
            'A' => 'SKU',
            'B' => 'Nombre',
            'C' => 'Descripcion',
            'D' => 'Categoria',
            'E' => 'Unidad',
            'F' => 'Stock_Minimo',
            'G' => 'Margen_Porcentaje',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue("{$col}1", $header);
        }

        // Estilo de cabeceras
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0C447C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ─── Filas de ejemplo ─────────────────────────────────────────────
        $examples = [
            ['MAT-001', 'Cable eléctrico 10mm', 'Cable de cobre para instalaciones', 'Electricidad', 'MT', 50, 35],
            ['MAT-002', 'Casco de seguridad',   'Casco amarillo clase A',             'EPP',          'UND', 10, 35],
            ['MAT-003', 'Aceite hidráulico',    'Aceite ISO 46 para maquinaria',      'Lubricantes',  'LT',  20, 40],
        ];

        foreach ($examples as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $col = chr(65 + $colIndex);
                $sheet->setCellValue("{$col}" . ($rowIndex + 2), $value);
            }
        }

        // ─── Hoja de referencia de Unidades ──────────────────────────────
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Unidades');
        $refSheet->setCellValue('A1', 'Abreviatura');
        $refSheet->setCellValue('B1', 'Nombre');
        $refSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $units = UnitOfMeasure::orderBy('abbreviation')->get();
        foreach ($units as $i => $unit) {
            $refSheet->setCellValue('A' . ($i + 2), $unit->abbreviation);
            $refSheet->setCellValue('B' . ($i + 2), $unit->name);
        }

        // ─── Hoja de referencia de Categorías ────────────────────────────
        $catSheet = $spreadsheet->createSheet();
        $catSheet->setTitle('Categorias');
        $catSheet->setCellValue('A1', 'Nombre');
        $catSheet->getStyle('A1')->getFont()->setBold(true);

        $categories = ProductCategory::orderBy('name')->get();
        foreach ($categories as $i => $cat) {
            $catSheet->setCellValue('A' . ($i + 2), $cat->name);
        }

        // Auto-size columnas en hoja principal
        $spreadsheet->setActiveSheetIndex(0);
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'plantilla_productos_erp.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}