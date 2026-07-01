<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Plan Contable General Empresarial (PCGE) - Cuentas esenciales para compras.
     * DEBE: 60 (Compras) + 40 (IGV) = HABER: 42 (Cuentas por pagar)
     */
    public function run(): void
    {
        $accounts = [
            // ─── ACTIVO ──────────────────────────────────────────────────────
            ['code' => '10',      'name' => 'Efectivo y Equivalentes de Efectivo', 'type' => 'activo', 'parent' => null],
            ['code' => '104',     'name' => 'Cuentas Corrientes en Instituciones Financieras', 'type' => 'activo', 'parent' => '10'],
            ['code' => '1041',    'name' => 'Cuentas Corrientes Operativas', 'type' => 'activo', 'parent' => '104'],
            ['code' => '20',      'name' => 'Mercaderías', 'type' => 'activo', 'parent' => null],
            ['code' => '201',     'name' => 'Mercaderías Manufacturadas', 'type' => 'activo', 'parent' => '20'],
            ['code' => '25',      'name' => 'Materiales Auxiliares, Suministros y Repuestos', 'type' => 'activo', 'parent' => null],
            ['code' => '252',     'name' => 'Suministros', 'type' => 'activo', 'parent' => '25'],

            // ─── PASIVO ───────────────────────────────────────────────────────
            ['code' => '40',      'name' => 'Tributos, Contraprestaciones y Aportes al Sistema', 'type' => 'pasivo', 'parent' => null],
            ['code' => '401',     'name' => 'Gobierno Central', 'type' => 'pasivo', 'parent' => '40'],
            ['code' => '4011',    'name' => 'Impuesto General a las Ventas', 'type' => 'pasivo', 'parent' => '401'],
            ['code' => '40111',   'name' => 'IGV - Cuenta Propia', 'type' => 'pasivo', 'parent' => '4011'],
            ['code' => '42',      'name' => 'Cuentas por Pagar Comerciales - Terceros', 'type' => 'pasivo', 'parent' => null],
            ['code' => '421',     'name' => 'Facturas, Boletas y otros Comprobantes por Pagar', 'type' => 'pasivo', 'parent' => '42'],
            ['code' => '4212',    'name' => 'Emitidas', 'type' => 'pasivo', 'parent' => '421'],

            // ─── GASTO / COMPRAS ─────────────────────────────────────────────
            ['code' => '60',      'name' => 'Compras', 'type' => 'gasto', 'parent' => null],
            ['code' => '601',     'name' => 'Mercaderías', 'type' => 'gasto', 'parent' => '60'],
            ['code' => '6011',    'name' => 'Mercaderías Manufacturadas', 'type' => 'gasto', 'parent' => '601'],
            ['code' => '602',     'name' => 'Materias Primas', 'type' => 'gasto', 'parent' => '60'],
            ['code' => '606',     'name' => 'Suministros', 'type' => 'gasto', 'parent' => '60'],
            ['code' => '6061',    'name' => 'Suministros Diversos', 'type' => 'gasto', 'parent' => '606'],
            ['code' => '609',     'name' => 'Costos Vinculados con las Compras', 'type' => 'gasto', 'parent' => '60'],
        ];

        $idMap = [];

        foreach ($accounts as $account) {
            $parentId = null;
            if ($account['parent']) {
                $parentId = $idMap[$account['parent']] ?? null;
            }

            $record = ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'parent_id' => $parentId,
                    'name'      => $account['name'],
                    'type'      => $account['type'],
                    'status'    => 'activo',
                ]
            );

            $idMap[$account['code']] = $record->id;
        }
    }
}
