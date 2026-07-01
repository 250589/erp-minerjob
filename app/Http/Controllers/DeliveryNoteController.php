<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryNoteRequest;
use App\Models\Area;
use App\Models\DeliveryNote;
use App\Models\Requirement;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\KardexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryNoteController extends Controller
{
    public function __construct(private KardexService $kardex) {}

    // ─── Listado ──────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $notes = DeliveryNote::with([
            'warehouse:id,code,name',
            'area:id,name',
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

        $notes->through(fn ($n) => $n->append(['status_label', 'status_color']));

        return Inertia::render('Deliveries/Index', [
            'notes'   => $notes,
            'filters' => $request->only(['search', 'status']),
            'counts'  => [
                'borrador'  => DeliveryNote::where('status', 'borrador')->count(),
                'entregada' => DeliveryNote::where('status', 'entregada')->count(),
            ],
        ]);
    }

    // ─── Formulario (Paso 39) ─────────────────────────────────

    public function create(): Response
    {
        return Inertia::render('Deliveries/Create', [
            'warehouses'   => Warehouse::active()->get(['id', 'code', 'name', 'type']),
            'areas'        => Area::orderBy('name')->get(['id', 'code', 'name']),
            'requirements' => Requirement::whereIn('status', ['aprobado', 'en_cotizacion'])
                ->get(['id', 'code']),
            'products'     => \App\Models\Product::active()
                ->get(['id', 'sku', 'name']),
            'stockSummary' => Stock::where('quantity', '>', 0)
                ->get(['warehouse_id', 'product_id', 'quantity', 'average_cost']),
        ]);
    }

    // ─── Crear nota de entrega ────────────────────────────────

    public function store(StoreDeliveryNoteRequest $request)
    {
        DB::transaction(function () use ($request) {
            $note = DeliveryNote::create([
                'warehouse_id'   => $request->warehouse_id,
                'area_id'        => $request->area_id,
                'requirement_id' => $request->requirement_id,
                'code'           => DeliveryNote::generateCode(),
                'requested_by'   => auth()->id(),
                'notes'          => $request->notes,
                'status'         => 'borrador',
            ]);

            foreach ($request->items as $item) {
                $note->items()->create([
                    'product_id'         => $item['product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                    'notes'              => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('deliveries.index')
            ->with('success', 'Nota de entrega NE generada. (Paso 39)');
    }

    // ─── Detalle ──────────────────────────────────────────────

    public function show(DeliveryNote $delivery): Response
    {
        $delivery->load([
            'warehouse:id,code,name',
            'area:id,name',
            'requirement:id,code',
            'requestedBy:id,name',
            'deliveredBy:id,name',
            'items.product:id,sku,name',
        ]);

        return Inertia::render('Deliveries/Show', [
            'delivery' => $delivery->append(['status_label', 'status_color']),
        ]);
    }

    // ─── Pasos 40-41: Confirmar entrega → salida kardex ──────

    public function deliver(DeliveryNote $delivery)
    {
        abort_if(
            $delivery->status !== 'borrador',
            403,
            'Esta nota de entrega ya fue procesada.'
        );

        $delivery->load(['items.product', 'warehouse', 'area']);
        $warehouse = $delivery->warehouse;

        DB::transaction(function () use ($delivery, $warehouse) {
            foreach ($delivery->items as $item) {
                // Validar stock suficiente
                $this->kardex->validateSufficientStock(
                    $warehouse,
                    $item->product,
                    (float) $item->quantity_requested
                );

                // Obtener costo promedio actual y congelarlo
                $unitCost = Stock::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $item->product_id)
                    ->value('average_cost') ?? 0;

                // Paso 40: Salida del kardex del subalmacén
                $areaName = $delivery->area?->name ?? 'Personal';

                $this->kardex->record(
                    warehouse:    $warehouse,
                    product:      $item->product,
                    movementType: 'salida_entrega',
                    reference:    $delivery,
                    quantity:     (float) $item->quantity_requested,
                    unitCost:     (float) $unitCost,
                    notes:        "Entrega {$delivery->code} → {$areaName}",
                    userId:       auth()->id(),
                );

                $item->update([
                    'quantity_delivered' => $item->quantity_requested,
                    'unit_cost'          => $unitCost,
                ]);
            }

            // Paso 41: Personal recibe — FLUJO COMPLETO
            $delivery->update([
                'status'       => 'entregada',
                'delivered_by' => auth()->id(),
                'delivered_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            "✓ Entrega confirmada. Stock descontado. Kardex actualizado. (Pasos 40-41) — FLUJO COMPLETO"
        );
    }
}