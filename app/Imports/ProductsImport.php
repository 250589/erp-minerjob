<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class ProductsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int   $created = 0;
    public int   $updated = 0;
    public array $errors  = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: fila 1 es cabecera

            // ─── Validar campos obligatorios ──────────────────────────────
            $sku    = strtoupper(trim($row['sku']          ?? ''));
            $nombre = trim($row['nombre']                  ?? '');
            $unidad = strtoupper(trim($row['unidad']       ?? ''));

            if (!$sku || !$nombre || !$unidad) {
                $this->errors[] = "Fila {$rowNumber}: SKU, Nombre y Unidad son obligatorios.";
                continue;
            }

            // ─── Buscar unidad de medida ──────────────────────────────────
            $unit = UnitOfMeasure::where('abbreviation', $unidad)->first();
            if (!$unit) {
                $this->errors[] = "Fila {$rowNumber}: Unidad '{$unidad}' no encontrada en el sistema.";
                continue;
            }

            // ─── Buscar categoría (opcional) ──────────────────────────────
            $categoryName = trim($row['categoria'] ?? '');
            $category     = $categoryName
                ? ProductCategory::where('name', $categoryName)->first()
                : null;

            // ─── Datos del producto ───────────────────────────────────────
            $productData = [
                'name'              => $nombre,
                'description'       => trim($row['descripcion']      ?? ''),
                'unit_id'           => $unit->id,
                'category_id'       => $category?->id,
                'min_stock'         => is_numeric($row['stock_minimo']        ?? '') ? (float) $row['stock_minimo']        : 0,
                'markup_percentage' => is_numeric($row['margen_porcentaje']   ?? '') ? (float) $row['margen_porcentaje']   : 35,
                'status'            => 'activo',
            ];

            // ─── Crear o actualizar por SKU ───────────────────────────────
            $existing = Product::withTrashed()->where('sku', $sku)->first();

            if ($existing) {
                $existing->restore(); // restaurar si estaba eliminado
                $existing->update($productData);
                $this->updated++;
            } else {
                Product::create(array_merge($productData, ['sku' => $sku]));
                $this->created++;
            }
        }
    }
}