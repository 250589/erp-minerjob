<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['code' => 'SOLICITANTE', 'name' => 'Solicitante'],
            ['code' => 'COMPRAS', 'name' => 'Compras'],
            ['code' => 'GERENCIA', 'name' => 'Gerencia'],
            ['code' => 'PROVEEDOR', 'name' => 'Proveedor'],
            ['code' => 'CONTABILIDAD', 'name' => 'Contabilidad'],
            ['code' => 'FINANZAS', 'name' => 'Finanzas'],
            ['code' => 'ALMACEN_PRINCIPAL', 'name' => 'Almacén Principal'],
            ['code' => 'TRASLADOS', 'name' => 'Traslado entre Almacenes'],
            ['code' => 'SUBALMACEN', 'name' => 'Subalmacén'],
            ['code' => 'CONSUMO_FINAL', 'name' => 'Consumo Final'],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(['code' => $area['code']], $area);
        }
    }
}
