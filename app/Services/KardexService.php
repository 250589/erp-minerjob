<?php

namespace App\Services;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class KardexService
{
    /**
     * Registra un movimiento de inventario y actualiza el stock resumen.
     * Toda escritura en kardex_movements y stocks DEBE pasar por aquí.
     *
     * @param  Warehouse   $warehouse     Almacén afectado
     * @param  Product     $product       Producto afectado
     * @param  string      $movementType  Tipo (ingreso_compra, salida_traslado, etc.)
     * @param  Model       $reference     Modelo origen (WarehouseReception, TransferOrder, DeliveryNote…)
     * @param  float       $quantity      Cantidad positiva (el servicio la invierte si es salida)
     * @param  float       $unitCost      Costo unitario en moneda base
     * @param  string|null $notes         Observación opcional
     * @param  int|null    $userId        Usuario que genera el movimiento
     */
    public function record(
        Warehouse $warehouse,
        Product   $product,
        string    $movementType,
        Model     $reference,
        float     $quantity,
        float     $unitCost,
        ?string   $notes = null,
        ?int      $userId = null,
    ): KardexMovement {
        return DB::transaction(function () use (
            $warehouse, $product, $movementType, $reference,
            $quantity, $unitCost, $notes, $userId
        ) {
            // 1. Determinar si es entrada o salida
            $isEntry = in_array($movementType, [
                'ingreso_compra',
                'entrada_traslado',
                'ajuste_positivo',
            ]);

            $signedQty = $isEntry ? abs($quantity) : -abs($quantity);

            // 2. Obtener o crear el registro de stock (con bloqueo pesimista)
            $stock = Stock::lockForUpdate()->firstOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id'   => $product->id,
                ],
                [
                    'quantity'     => 0,
                    'average_cost' => 0,
                ]
            );

            // 3. Calcular nuevo costo promedio ponderado (solo en entradas)
            $newQuantity = $stock->quantity + $signedQty;

            if ($isEntry && $newQuantity > 0) {
                $totalPrevValue = $stock->quantity * $stock->average_cost;
                $newValue       = abs($signedQty) * $unitCost;
                $newAverageCost = ($totalPrevValue + $newValue) / $newQuantity;
            } else {
                $newAverageCost = $stock->average_cost; // las salidas conservan el costo promedio
            }

            $newBalanceValue = round($newQuantity * $newAverageCost, 4);

            // 4. Insertar movimiento (APPEND ONLY — nunca se edita)
            $movement = KardexMovement::create([
                'warehouse_id'     => $warehouse->id,
                'product_id'       => $product->id,
                'movement_type'    => $movementType,
                'reference_type'   => $reference->getMorphClass(),
                'reference_id'     => $reference->getKey(),
                'quantity'         => $signedQty,
                'unit_cost'        => $unitCost,
                'balance_quantity'  => round($newQuantity, 4),
                'balance_value'    => $newBalanceValue,
                'movement_date'    => now(),
                'notes'            => $notes,
                'created_by'       => $userId,
            ]);

            // 5. Actualizar resumen de stock
            $stock->update([
                'quantity'     => round($newQuantity, 4),
                'average_cost' => round($newAverageCost, 4),
                'updated_at'   => now(),
            ]);

            // 6. Actualizar precio de compra y venta vigentes en el producto (solo entradas)
            if ($isEntry && $movementType === 'ingreso_compra') {
                $salePrice = round($unitCost * (1 + $product->markup_percentage / 100), 4);

                $product->update([
                    'current_purchase_price' => $unitCost,
                    'current_sale_price'     => $salePrice,
                ]);
            }

            return $movement;
        });
    }

    /**
     * Devuelve el stock actual de un producto en un almacén.
     * Usar este método en controllers en lugar de consultar stocks directamente.
     */
    public function currentStock(Warehouse $warehouse, Product $product): float
    {
        return Stock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity') ?? 0.0;
    }

    /**
     * Valida que haya suficiente stock antes de una salida.
     * Lanza excepción si el stock es insuficiente.
     *
     * @throws \RuntimeException
     */
    public function validateSufficientStock(
        Warehouse $warehouse,
        Product   $product,
        float     $quantityNeeded
    ): void {
        $available = $this->currentStock($warehouse, $product);

        if ($available < $quantityNeeded) {
            throw new \RuntimeException(
                "Stock insuficiente para [{$product->name}] en [{$warehouse->name}]. "
                . "Disponible: {$available}, Requerido: {$quantityNeeded}"
            );
        }
    }
}
