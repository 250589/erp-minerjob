<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Unidades de medida ────────────────────────────────────────
        $units = [
            ['name' => 'Unidad',     'abbreviation' => 'UND'],
            ['name' => 'Kilogramo',  'abbreviation' => 'KG'],
            ['name' => 'Litro',      'abbreviation' => 'LT'],
            ['name' => 'Metro',      'abbreviation' => 'M'],
            ['name' => 'Caja',       'abbreviation' => 'CJA'],
            ['name' => 'Galón',      'abbreviation' => 'GAL'],
            ['name' => 'Rollo',      'abbreviation' => 'ROL'],
            ['name' => 'Par',        'abbreviation' => 'PAR'],
            ['name' => 'Bolsa',      'abbreviation' => 'BLS'],
            ['name' => 'Tonelada',   'abbreviation' => 'TN'],
        ];

        foreach ($units as $unit) {
            UnitOfMeasure::updateOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                $unit
            );
        }

        // ─── Categorías de productos ───────────────────────────────────
        $categories = [
            ['name' => 'Equipos y Herramientas', 'children' => [
                'Herramientas Manuales',
                'Herramientas Eléctricas',
                'Equipos de Medición',
            ]],
            ['name' => 'Materiales de Construcción', 'children' => [
                'Cemento y Concreto',
                'Fierros y Acero',
                'Tuberías y Conexiones',
            ]],
            ['name' => 'Insumos de Seguridad', 'children' => [
                'EPP (Equipo de Protección Personal)',
                'Señalización',
            ]],
            ['name' => 'Lubricantes y Combustibles', 'children' => [
                'Aceites',
                'Grasas',
                'Combustibles',
            ]],
            ['name' => 'Artículos de Oficina', 'children' => []],
        ];

        foreach ($categories as $cat) {
            $parent = ProductCategory::updateOrCreate(
                ['name' => $cat['name'], 'parent_id' => null],
                ['name' => $cat['name']]
            );

            foreach ($cat['children'] as $child) {
                ProductCategory::updateOrCreate(
                    ['name' => $child, 'parent_id' => $parent->id],
                    ['name' => $child, 'parent_id' => $parent->id]
                );
            }
        }

        // ─── Almacenes ─────────────────────────────────────────────────
        $main = Warehouse::updateOrCreate(
            ['code' => 'ALM-PRINCIPAL'],
            [
                'code'   => 'ALM-PRINCIPAL',
                'name'   => 'Almacén Principal',
                'type'   => 'principal',
                'status' => 'activo',
            ]
        );

        $subalmacenes = [
            ['code' => 'SUB-OPERACIONES', 'name' => 'Subalmacén Operaciones'],
            ['code' => 'SUB-MANTENIMIENTO', 'name' => 'Subalmacén Mantenimiento'],
        ];

        foreach ($subalmacenes as $sub) {
            Warehouse::updateOrCreate(
                ['code' => $sub['code']],
                [
                    'code'                => $sub['code'],
                    'name'                => $sub['name'],
                    'type'                => 'subalmacen',
                    'parent_warehouse_id' => $main->id,
                    'status'              => 'activo',
                ]
            );
        }
    }
}
