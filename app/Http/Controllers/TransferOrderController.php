<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiveTransferRequest;
use App\Http\Requests\StoreTransferOrderRequest;
use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\TransferGuide;
use App\Models\TransferOrder;
use App\Models\TransferOrderItem;
use App\Models\TransferReception;
use App\Models\Warehouse;
use App\Services\KardexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransferOrderController extends Controller
{
    public function __construct(private KardexService $kardex) {}

    // ─── Listado ──────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $transfers = TransferOrder::with([
            'originWarehouse:id,code,name',
            'destinationWarehouse:id,code,name',
            'requestedBy:id,name',
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

        $transfers->through(
            fn ($t) => $t->append(['status_label', 'status_color'])
        );

        return Inertia::render('Transfers/Index', [
            'transfers' => $transfers,
            'filters'   => $request->only(['search', 'status']),
        ]);
    }

    // ─── Formulario (Paso 29) ─────────────────────────────────

    public function create(): Response
    {
        $warehouses = Warehouse::active()->get(['id', 'code', 'name', 'type']);

        // Stock disponible por almacén y producto para validar antes de crear
        $stockSummary = Stock::with(['product:id,sku,name', 'warehouse:id,name'])
            ->where('quantity', '>', 0)
            ->get(['warehouse_id', 'product_id', 'quantity', 'average_cost']);

        return Inertia::render('Transfers/Create', [
            'warehouses'   => $warehouses,
            'products'     => Product::active()->get(['id', 'sku', 'name']),
            'stockSummary' => $stockSummary,
        ]);
    }

    // ─── Crear orden de traslado ──────────────────────────────

    public function store(StoreTransferOrderRequest $request)
    {
        DB::transaction(function () use ($request) {
            $transfer = TransferOrder::create([
                'origin_warehouse_id'      => $request->origin_warehouse_id,
                'destination_warehouse_id' => $request->destination_warehouse_id,
                'requested_by'             => auth()->id(),
                'code'                     => TransferOrder::generateCode(),
                'status'                   => 'creada',
            ]);

            foreach ($request->items as $item) {
                $transfer->items()->create([
                    'product_id'         => $item['product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }
        });

        return redirect()->route('transfers.index')
            ->with('success', 'Orden de traslado creada. (Paso 29)');
    }

    // ─── Detalle ──────────────────────────────────────────────

    public function show(TransferOrder $transfer): Response
    {
        $transfer->load([
            'originWarehouse:id,code,name,type',
            'destinationWarehouse:id,code,name,type',
            'requestedBy:id,name',
            'items.product:id,sku,name',
            'guide.issuedBy:id,name',
            'reception.receivedBy:id,name',
            'reception.items.transferOrderItem.product:id,sku,name',
        ]);

        return Inertia::render('Transfers/Show', [
            'transfer' => $transfer->append(['status_label', 'status_color']),
        ]);
    }

    // ─── Paso 31: Despachar (salida kardex origen) ────────────

    public function dispatch(TransferOrder $transfer)
    {
        abort_if($transfer->status !== 'creada', 403,
            'Solo se puede despachar una orden en estado Creada.'
        );

        $transfer->load(['items.product', 'originWarehouse', 'destinationWarehouse']);
        $originWarehouse = $transfer->originWarehouse;

        DB::transaction(function () use ($transfer, $originWarehouse) {
            foreach ($transfer->items as $item) {
                // Validar stock suficiente en origen
                $this->kardex->validateSufficientStock(
                    $originWarehouse,
                    $item->product,
                    (float) $item->quantity_requested
                );

                // Obtener costo promedio actual del origen (se congela)
                $unitCost = Stock::where('warehouse_id', $originWarehouse->id)
                    ->where('product_id', $item->product_id)
                    ->value('average_cost') ?? 0;

                // Paso 31: Registrar SALIDA en kardex del almacén origen
                $this->kardex->record(
                    warehouse:    $originWarehouse,
                    product:      $item->product,
                    movementType: 'salida_traslado',
                    reference:    $transfer,
                    quantity:     (float) $item->quantity_requested,
                    unitCost:     (float) $unitCost,
                    notes:        "Traslado {$transfer->code} → {$transfer->destinationWarehouse->name}",
                    userId:       auth()->id(),
                );

                // Guardar el costo y la cantidad despachada en el ítem
                $item->update([
                    'quantity_sent' => $item->quantity_requested,
                    'unit_cost'     => $unitCost,
                ]);
            }

            // Paso 30: Generar guía interna de traslado automáticamente
            TransferGuide::create([
                'transfer_order_id' => $transfer->id,
                'guide_number'      => TransferGuide::generateNumber(),
                'issued_by'         => auth()->id(),
                'issued_at'         => now(),
            ]);

            $transfer->update(['status' => 'en_transito']);
        });

        return back()->with('success',
            "Mercadería despachada. Guía generada. Salida registrada en kardex de {$originWarehouse->name}. (Pasos 30-31)"
        );
    }

    // ─── Pasos 32-34: Confirmar recepción en destino ──────────

    public function receive(ReceiveTransferRequest $request, TransferOrder $transfer)
    {
        abort_if($transfer->status !== 'en_transito', 403,
            'Solo se puede confirmar una recepción en estado En Tránsito.'
        );

        $transfer->load(['items.product', 'destinationWarehouse', 'originWarehouse']);
        $destWarehouse = $transfer->destinationWarehouse;

        DB::transaction(function () use ($request, $transfer, $destWarehouse) {
            // Crear registro de recepción
            $reception = TransferReception::create([
                'transfer_order_id' => $transfer->id,
                'received_by'       => auth()->id(),
                'received_at'       => now(),
                'status'            => $request->status,
                'observations'      => $request->observations,
            ]);

            foreach ($request->items as $itemData) {
                $orderItem = TransferOrderItem::with('product')
                    ->findOrFail($itemData['transfer_order_item_id']);

                // Crear ítem de recepción
                $reception->items()->create([
                    'transfer_order_item_id' => $orderItem->id,
                    'quantity_received'      => $itemData['quantity_received'],
                    'condition_status'       => $itemData['condition_status'],
                    'notes'                  => $itemData['notes'] ?? null,
                ]);

                // Actualizar cantidad recibida en el ítem de la orden
                $orderItem->update(['quantity_received' => $itemData['quantity_received']]);

                // Paso 32: Registrar ENTRADA en kardex del almacén destino
                if ($itemData['condition_status'] === 'bueno' && $itemData['quantity_received'] > 0) {
                    $this->kardex->record(
                        warehouse:    $destWarehouse,
                        product:      $orderItem->product,
                        movementType: 'entrada_traslado',
                        reference:    $transfer,
                        quantity:     (float) $itemData['quantity_received'],
                        unitCost:     (float) ($orderItem->unit_cost ?? 0),
                        notes:        "Traslado {$transfer->code} desde {$transfer->originWarehouse->name}",
                        userId:       auth()->id(),
                    );
                }
            }

            // Pasos 33-34: Stock y kardex actualizados. Marcar orden como recibida.
            $transfer->update(['status' => 'recibida']);
        });

        return back()->with('success',
            "Traslado recibido en {$destWarehouse->name}. Stock y kardex actualizados. (Pasos 32-34)"
        );
    }
}
