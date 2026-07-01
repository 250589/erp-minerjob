<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterQuoteRequest;
use App\Models\Quote;
use App\Models\QuoteComparison;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    // ─── Formulario para registrar cotización recibida ────────

    public function create(QuoteRequest $quoteRequest): Response
    {
        abort_if($quoteRequest->status !== 'abierta', 403);

        $quoteRequest->load([
            'requirement:id,code',
            'requirement.items.unit:id,name,abbreviation',
            'suppliers.supplier:id,business_name,trade_name,tax_id',
        ]);

        // Solo proveedores invitados que aún no han respondido
        $pendingSuppliers = $quoteRequest->suppliers
            ->where('status', 'pendiente')
            ->map(fn ($qrs) => $qrs->supplier);

        return Inertia::render('Quotes/Register', [
            'quoteRequest'    => $quoteRequest,
            'pendingSuppliers'=> $pendingSuppliers->values(),
        ]);
    }

    // ─── Guardar cotización recibida (paso 4) ─────────────────

    public function store(RegisterQuoteRequest $request, QuoteRequest $quoteRequest)
    {
        abort_if($quoteRequest->status !== 'abierta', 403);

        DB::transaction(function () use ($request, $quoteRequest) {
            // Calcular subtotal, IGV y total
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

    // ─── Seleccionar cotización ganadora (pasos 6-8) ──────────
    // Paso 5 (comparativo) se calcula en el frontend.
    // Gerencia aprueba en el Módulo 6 (Aprobaciones).
    // Aquí Compras pre-selecciona la mejor y la envía a aprobación.

    public function selectWinner(Request $request, QuoteRequest $quoteRequest)
    {
        $request->validate([
            'quote_id' => 'required|exists:quotes,id',
        ]);

        DB::transaction(function () use ($request, $quoteRequest) {
            // Rechazar todas las demás
            Quote::where('quote_request_id', $quoteRequest->id)
                ->where('id', '!=', $request->quote_id)
                ->update(['status' => 'rechazada']);

            // Marcar la seleccionada como "comparada" (esperando aprobación gerencia)
            Quote::find($request->quote_id)->update(['status' => 'comparada']);

            // Guardar selección en el comparativo
            QuoteComparison::updateOrCreate(
                ['quote_request_id' => $quoteRequest->id],
                [
                    'selected_quote_id' => $request->quote_id,
                    'generated_by'      => auth()->id(),
                    'generated_at'      => now(),
                ]
            );

            // Cerrar la solicitud de cotización
            $quoteRequest->update(['status' => 'cerrada']);
        });

        return redirect()->route('quote-requests.show', $quoteRequest)
            ->with('success', 'Cotización seleccionada. Pendiente de aprobación gerencial.');
    }
}
