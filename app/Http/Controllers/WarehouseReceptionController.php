<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseReceptionRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Models\WarehouseReception;
use App\Services\KardexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseReceptionController extends Controller
{
    public function __construct(private KardexService $kardex) {}

    // ─── Listado ──────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $receptions = WarehouseReception::with([
            'purchaseOrder:id,code',
            'warehouse:id,code,name',
            'receivedBy:id,name',
        ])
            ->when($request->search, fn ($q) =>
                $q->where('code', 'like', "%{$request->search}%")
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $receptions->through(
            fn ($r) => $r->append(['status_label', 'status_color'])
        );

        return Inertia::render('WarehouseReceptions/Index', [
            'receptions' => $receptions,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    // ─── Formulario de recepción (Paso 21) ───────────────────

    public function create(Request $request): Response
    {
        $purchaseOrder = null;
        if ($request->filled('purchase_order_id')) {
            $purchaseOrder = PurchaseOrder::with([
                'supplier:id,business_name',
                'items.product:id,sku,name,markup_percentage',
            ])->findOrFail($request->purchase_order_id);
        }

        return Inertia::render('WarehouseReceptions/Create', [
            'purchaseOrder'  => $purchaseOrder,
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['enviada', 'facturada'])
                ->with('supplier:id,business_name')
                ->get(['id', 'code', 'supplier_id']),
            'warehouses'     => Warehouse::active()
                ->where('type', 'principal')
                ->get(['id', 'code', 'name']),
        ]);
    }

    // ─── Guardar recepción (Pasos 23-27) ─────────────────────

    public function store(StoreWarehouseReceptionRequest $request)
    {
        DB::transaction(function () use ($request) {
            $reception = WarehouseReception::create([
                'purchase_order_id' => $request->purchase_order_id,
                'warehouse_id'      => $request->warehouse_id,
                'invoice_id'        => $request->invoice_id,
                'code'              => WarehouseReception::generateCode(),
                'received_by'       => auth()->id(),
                'received_at'       => now(),
                'status'            => $request->status,
                'observations'      => $request->observations,
            ]);

            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $markup  = (float) ($item['markup_percentage'] ?? $product->markup_percentage);

                // Paso 25: precio_venta = precio_compra * (1 + markup/100)
                $salePrice = round($item['unit_purchase_price'] * (1 + $markup / 100), 4);

                // Paso 23-24: Crear ítem de recepción
                $reception->items()->create([
                    'purchase_order_item_id'     => $item['purchase_order_item_id'] ?? null,
                    'product_id'                 => $product->id,
                    'quantity_ordered'           => $item['quantity_ordered'],
                    'quantity_received'          => $item['quantity_received'],
                    'unit_purchase_price'        => $item['unit_purchase_price'],
                    'markup_percentage_applied'  => $markup,
                    'unit_sale_price'            => $salePrice,
                    'condition_status'           => $item['condition_status'],
                    'notes'                      => $item['notes'] ?? null,
                ]);

                // Pasos 26-27: Solo ingresar al kardex si está en buen estado
                if ($item['condition_status'] === 'bueno' && $item['quantity_received'] > 0) {
                    $this->kardex->record(
                        warehouse:    $warehouse,
                        product:      $product,
                        movementType: 'ingreso_compra',
                        reference:    $reception,
                        quantity:     (float) $item['quantity_received'],
                        unitCost:     (float) $item['unit_purchase_price'],
                        notes:        "Recepción {$reception->code}",
                        userId:       auth()->id(),
                    );
                }
            }
        });

        return redirect()->route('warehouse-receptions.index')
            ->with('success', 'Recepción registrada. Stock y Kardex actualizados. (Pasos 23-27)');
    }

    // ─── Detalle ──────────────────────────────────────────────

    public function show(WarehouseReception $warehouseReception): Response
    {
        $warehouseReception->load([
            'purchaseOrder:id,code',
            'warehouse:id,code,name',
            'invoice:id,series,number',
            'items.product:id,sku,name',
            'receivedBy:id,name',
        ]);

        return Inertia::render('WarehouseReceptions/Show', [
            'reception' => $warehouseReception->append(['status_label', 'status_color']),
        ]);
    }
}
