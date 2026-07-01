<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Crear todos los permisos ─────────────────────────────────────────
        $permissions = [
            // Requerimientos
            'requirements.view', 'requirements.create',
            'requirements.edit', 'requirements.send',

            // Cotizaciones
            'quote_requests.view', 'quote_requests.create', 'quote_requests.manage',
            'quotes.create',

            // Aprobaciones
            'approvals.view', 'approvals.decide',

            // Órdenes de Compra
            'purchase_orders.view', 'purchase_orders.send',

            // Proveedores
            'suppliers.view', 'suppliers.manage',

            // Facturas
            'invoices.view', 'invoices.create', 'invoices.validate',

            // Contabilidad
            'accounting_entries.create',

            // Pagos
            'payments.view', 'payments.create', 'payments.confirm',

            // Almacén
            'warehouse_receptions.view', 'warehouse_receptions.create',
            'stock.view', 'kardex.view',

            // Traslados
            'transfers.view', 'transfers.create',
            'transfers.dispatch', 'transfers.receive',

            // Entregas
            'deliveries.view', 'deliveries.create', 'deliveries.deliver',

            // Administración
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ─── Crear roles con sus permisos ────────────────────────────────────

        // Administrador — acceso total
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Solicitante — Carril 1 del flujograma
        $solicitante = Role::firstOrCreate(['name' => 'Solicitante', 'guard_name' => 'web']);
        $solicitante->syncPermissions([
            'requirements.view', 'requirements.create',
            'requirements.edit', 'requirements.send',
            'deliveries.view',
        ]);

        // Compras — Carril 2
        $compras = Role::firstOrCreate(['name' => 'Compras', 'guard_name' => 'web']);
        $compras->syncPermissions([
            'requirements.view',
            'quote_requests.view', 'quote_requests.create', 'quote_requests.manage',
            'quotes.create',
            'purchase_orders.view', 'purchase_orders.send',
            'suppliers.view', 'suppliers.manage',
            'invoices.view',
        ]);

        // Gerencia — Carril 3
        $gerencia = Role::firstOrCreate(['name' => 'Gerencia', 'guard_name' => 'web']);
        $gerencia->syncPermissions([
            'approvals.view', 'approvals.decide',
            'requirements.view',
            'purchase_orders.view',
            'invoices.view',
            'payments.view',
            'stock.view', 'kardex.view',
        ]);

        // Contabilidad — Carril 5
        $contabilidad = Role::firstOrCreate(['name' => 'Contabilidad', 'guard_name' => 'web']);
        $contabilidad->syncPermissions([
            'invoices.view', 'invoices.create', 'invoices.validate',
            'accounting_entries.create',
            'purchase_orders.view',
        ]);

        // Finanzas — Carril 6
        $finanzas = Role::firstOrCreate(['name' => 'Finanzas', 'guard_name' => 'web']);
        $finanzas->syncPermissions([
            'payments.view', 'payments.create', 'payments.confirm',
            'invoices.view',
        ]);

        // Almacen Principal — Carril 7
        $almacen = Role::firstOrCreate(['name' => 'Almacen_Principal', 'guard_name' => 'web']);
        $almacen->syncPermissions([
            'warehouse_receptions.view', 'warehouse_receptions.create',
            'stock.view', 'kardex.view',
            'transfers.view', 'transfers.create', 'transfers.dispatch',
            'purchase_orders.view',
        ]);

        // Traslados — Carril 8
        $traslados = Role::firstOrCreate(['name' => 'Traslados', 'guard_name' => 'web']);
        $traslados->syncPermissions([
            'transfers.view', 'transfers.create', 'transfers.dispatch',
            'stock.view', 'kardex.view',
        ]);

        // Subalmacen — Carril 9
        $subalmacen = Role::firstOrCreate(['name' => 'Subalmacen', 'guard_name' => 'web']);
        $subalmacen->syncPermissions([
            'transfers.view', 'transfers.receive',
            'stock.view', 'kardex.view',
            'deliveries.view', 'deliveries.create', 'deliveries.deliver',
        ]);

        // Consumo Final — Carril 10
        $consumo = Role::firstOrCreate(['name' => 'Consumo_Final', 'guard_name' => 'web']);
        $consumo->syncPermissions([
            'requirements.view', 'requirements.create',
            'deliveries.view',
        ]);

        $this->command->info('✓ 10 roles y ' . count($permissions) . ' permisos creados.');
    }
}