<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceObservation;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    // ─── Listado ──────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $invoices = Invoice::with([
            'supplier:id,business_name',
            'purchaseOrder:id,code',
        ])
            ->when($request->search, fn ($q) =>
                $q->where('number', 'like', "%{$request->search}%")
                  ->orWhere('series', 'like', "%{$request->search}%")
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $invoices->through(
            fn ($inv) => $inv->append(['status_label', 'status_color', 'full_number'])
        );

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    // ─── Crear factura (Paso 10) ──────────────────────────────

    public function create(Request $request): Response
    {
        $purchaseOrder = null;
        if ($request->filled('purchase_order_id')) {
            $purchaseOrder = PurchaseOrder::with('items.product', 'supplier')
                ->findOrFail($request->purchase_order_id);
        }

        return Inertia::render('Invoices/Create', [
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['enviada'])
                ->get(['id', 'code', 'supplier_id']),
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        DB::transaction(function () use ($request) {
            $subtotal = collect($request->items)
                ->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
            $tax   = round($subtotal * 0.18, 2);
            $total = round($subtotal + $tax, 2);

            $invoice = Invoice::create([
                'purchase_order_id' => $request->purchase_order_id,
                'supplier_id'       => $request->supplier_id,
                'series'            => $request->series,
                'number'            => $request->number,
                'issue_date'        => $request->issue_date,
                'currency'          => $request->currency,
                'exchange_rate'     => $request->exchange_rate,
                'subtotal'          => round($subtotal, 2),
                'tax'               => $tax,
                'total'             => $total,
                'status'            => 'recibida',
                'received_at'       => now(),
            ]);

            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                    'description'            => $item['description'],
                    'quantity'               => $item['quantity'],
                    'unit_price'             => $item['unit_price'],
                    'subtotal'               => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            // Actualizar estado de la OC
            PurchaseOrder::find($request->purchase_order_id)
                ->update(['status' => 'facturada']);
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Factura registrada en el sistema. (Paso 10)');
    }

    // ─── Detalle ──────────────────────────────────────────────

    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'supplier',
            'purchaseOrder:id,code,total,currency',
            'items',
            'observations.createdBy:id,name',
            'accountingEntry.details.account',
            'validatedBy:id,name',
        ]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->append([
                'status_label', 'status_color',
                'full_number', 'available_actions',
            ]),
        ]);
    }

    // ─── Paso 11: Iniciar revisión ───────────────────────────

    public function startReview(Invoice $invoice)
    {
        abort_if($invoice->status !== 'recibida', 403);
        $invoice->update(['status' => 'en_revision']);
        return back()->with('success', 'Factura en revisión. (Paso 11)');
    }

    // ─── Paso 12A: Observar factura ───────────────────────────

    public function observe(Request $request, Invoice $invoice)
    {
        abort_if(!in_array($invoice->status, ['en_revision', 'observada']), 403);
        $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $invoice) {
            InvoiceObservation::create([
                'invoice_id' => $invoice->id,
                'comment'    => $request->comment,
                'created_by' => auth()->id(),
            ]);
            $invoice->update(['status' => 'observada']);

            // Aquí se notificaría al proveedor (por email/notificación)
        });

        return back()->with('success', 'Observación registrada. Se solicitó corrección al proveedor. (Paso 12A)');
    }

    // ─── Paso 12 SÍ: Resolver observaciones y validar ────────

    public function validate(Invoice $invoice)
    {
        abort_if(!in_array($invoice->status, ['en_revision', 'observada']), 403);

        // Resolver todas las observaciones pendientes
        $invoice->observations()
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);

        $invoice->update([
            'status'       => 'validada',
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        return back()->with('success', 'Factura validada correctamente. (Pasos 12-13)');
    }
}
