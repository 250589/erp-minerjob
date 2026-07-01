<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterQuoteRequest;
use App\Models\Approval;
use App\Models\Quote;
use App\Models\QuoteComparison;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    // ─── Formulario para registrar cotización recibida ────────────────────────

    public function create(QuoteRequest $quoteRequest): Response
    {
        abort_if($quoteRequest->status !== 'abierta', 403);

        $quoteRequest->load([
            'requirement:id,code',
            'requirement.items.unit:id,name,abbreviation',
            'suppliers.supplier:id,business_name,trade_name,tax_id',
        ]);

        $pendingSuppliers = $quoteRequest->suppliers
            ->where('status', 'pendiente')
            ->map(fn ($qrs) => $qrs->supplier);

        return Inertia::render('Quotes/Register', [
            'quoteRequest'     => $quoteRequest,
            'pendingSuppliers' => $pendingSuppliers->values(),
        ]);
    }

    // ─── Guardar cotización recibida (paso 4) ─────────────────────────────────

    public function store(RegisterQuoteRequest $request, QuoteRequest $quoteRequest)
    {
        abort_if($quoteRequest->status !== 'abierta', 403);

        DB::transaction(function () use ($request, $quoteRequest) {
            $subtotal = collect($request->items)
                ->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
            $tax   = round($subtotal * 0.18, 2);
            $total = round($subtotal + $tax, 2);

            $quote = $quoteRequest->quotes()->create([
                'supplier_id'        => $request->supplier_id,
                'code'               => $request->code,
                'currency'           => $request->currency,
                'exchange_rate'      => $request->exchange_rate,
                'payment_term_days'  => $request->payment_term_days,
                'delivery_term_days' => $request->delivery_term_days,
                'validity_date'      => $request->validity_date,
                'subtotal'           => round($subtotal, 2),
                'tax'                => $tax,
                'total'              => $total,
                'notes'              => $request->notes,
                'status'             => 'recibida',
                'received_at'        => now(),
            ]);

            // Guardar archivo adjunto (PDF o imagen)
            if ($request->hasFile('quote_file')) {
                $path = $request->file('quote_file')->store('quotes', 'public');
                $quote->update(['file_path' => $path]);
            }

            foreach ($request->items as $item) {
                $quote->items()->create([
                    'requirement_item_id' => $item['requirement_item_id'] ?? null,
                    'description'         => $item['description'],
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['unit_price'],
                    'subtotal'            => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            // Marcar proveedor como "respondido"
            $quoteRequest->suppliers()
                ->where('supplier_id', $request->supplier_id)
                ->update(['status' => 'respondido', 'responded_at' => now()]);
        });

        return redirect()->route('quote-requests.show', $quoteRequest)
            ->with('success', 'Cotización registrada exitosamente.');
    }

    // ─── Seleccionar ganador → crea Aprobación para Gerencia (pasos 5-6) ─────

    public function selectWinner(Request $request, QuoteRequest $quoteRequest)
    {
        $request->validate([
            'quote_id' => 'required|exists:quotes,id',
        ]);

        DB::transaction(function () use ($request, $quoteRequest) {
            // Rechazar todas las demás cotizaciones
            Quote::where('quote_request_id', $quoteRequest->id)
                ->where('id', '!=', $request->quote_id)
                ->update(['status' => 'rechazada']);

            // Marcar la seleccionada como "comparada" (esperando aprobación)
            Quote::find($request->quote_id)->update(['status' => 'comparada']);

            // Guardar o actualizar el comparativo
            $comparison = QuoteComparison::updateOrCreate(
                ['quote_request_id' => $quoteRequest->id],
                [
                    'selected_quote_id' => $request->quote_id,
                    'generated_by'      => auth()->id(),
                    'generated_at'      => now(),
                ]
            );

            // Cerrar la solicitud de cotización
            $quoteRequest->update(['status' => 'cerrada']);

            // ─── PASO 6: Crear solicitud de APROBACIÓN para Gerencia ─────────
            // Eliminar aprobaciones anteriores de este mismo comparativo (si había)
            Approval::where('approvable_type', 'QuoteComparison')
                ->where('approvable_id', $comparison->id)
                ->delete();

            Approval::create([
                'approvable_type' => 'QuoteComparison',
                'approvable_id'   => $comparison->id,
                'requested_by'    => auth()->id(),
                'approver_id'     => null, // Gerencia lo asignará
                'status'          => 'pendiente',
            ]);
        });

        return redirect('/approvals')
            ->with('success',
                'Cotización seleccionada. Se creó solicitud de aprobación para Gerencia. (Paso 6)'
            );
    }

    // ─── Detalle de cotización ────────────────────────────────────────────────

    public function show(Quote $quote): Response
    {
        $quote->load([
            'supplier:id,business_name,tax_id',
            'quoteRequest:id,code,requirement_id',
            'items.product:id,sku,name',
        ]);

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
        ]);
    }
}