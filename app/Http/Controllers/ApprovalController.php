<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideApprovalRequest;
use App\Models\Approval;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    // ─── Listado de aprobaciones (vista Gerencia) ─────────────

    public function index(Request $request): Response
    {
        $approvals = Approval::with([
            'approvable.supplier:id,business_name',
            'approvable.quoteRequest.requirement:id,code',
            'requestedBy:id,name',
        ])
            ->when(
                !$request->filled('status') || $request->status === 'pendiente',
                fn ($q) => $q->where('status', 'pendiente'),
                fn ($q) => $q->where('status', $request->status)
            )
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $approvals->through(
            fn ($a) => $a->append(['status_label', 'status_color'])
        );

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'filters'   => $request->only(['status']),
            'counts'    => [
                'pendiente' => Approval::where('status', 'pendiente')->count(),
                'aprobado'  => Approval::where('status', 'aprobado')->count(),
                'rechazado' => Approval::where('status', 'rechazado')->count(),
            ],
        ]);
    }

    // ─── Detalle para evaluar (Paso 6 del flujograma) ─────────

    public function show(Approval $approval): Response
    {
        $approval->load([
            // La cotización seleccionada por Compras
            'approvable.supplier',
            'approvable.items',
            // El requerimiento original
            'approvable.quoteRequest.requirement.items.unit',
            // Todas las cotizaciones recibidas (para contexto comparativo)
            'approvable.quoteRequest.quotes.supplier:id,business_name',
            'approvable.quoteRequest.quotes.items',
            'requestedBy:id,name',
            'approver:id,name',
        ]);

        return Inertia::render('Approvals/Show', [
            'approval' => $approval->append(['status_label', 'status_color']),
        ]);
    }

    // ─── Procesar decisión (Paso 7: SÍ/NO) ───────────────────

    public function decide(DecideApprovalRequest $request, Approval $approval)
    {
        abort_if(
            $approval->status !== 'pendiente',
            403,
            'Esta aprobación ya fue procesada.'
        );

        $purchaseOrder = null;

        DB::transaction(function () use ($request, $approval, &$purchaseOrder) {
            $decision = $request->status; // 'aprobado' | 'rechazado'

            // 1. Registrar decisión
            $approval->update([
                'approver_id' => auth()->id(),
                'status'      => $decision,
                'comments'    => $request->comments,
                'decided_at'  => now(),
            ]);

            /** @var Quote $quote */
            $quote = $approval->approvable;
            $quote->load('items', 'quoteRequest.requirement');

            if ($decision === 'aprobado') {
                // 2. Marcar cotización como aprobada
                $quote->update(['status' => 'aprobada']);

                // 3. Generar Orden de Compra automáticamente (Paso 8)
                $purchaseOrder = PurchaseOrder::create([
                    'quote_id'           => $quote->id,
                    'requirement_id'     => $quote->quoteRequest->requirement_id,
                    'supplier_id'        => $quote->supplier_id,
                    'code'               => PurchaseOrder::generateCode(),
                    'currency'           => $quote->currency,
                    'exchange_rate'      => $quote->exchange_rate,
                    'subtotal'           => $quote->subtotal,
                    'tax'                => $quote->tax,
                    'total'              => $quote->total,
                    'payment_term_days'  => $quote->payment_term_days,
                    'delivery_term_days' => $quote->delivery_term_days,
                    'status'             => 'generada',
                    'created_by'         => auth()->id(),
                    'approved_by'        => auth()->id(),
                ]);

                // 4. Copiar ítems de la cotización a la OC
                foreach ($quote->items as $qItem) {
                    $purchaseOrder->items()->create([
                        'quote_item_id' => $qItem->id,
                        'product_id'    => $qItem->product_id,
                        'description'   => $qItem->description,
                        'quantity'      => $qItem->quantity,
                        'unit_price'    => $qItem->unit_price,
                        'subtotal'      => $qItem->subtotal,
                    ]);
                }

                // 5. Actualizar estado del requerimiento
                $quote->quoteRequest->requirement->update([
                    'status' => 'convertido_oc',
                ]);

            } else {
                // RECHAZADO: Compras debe solicitar nuevas cotizaciones
                $quote->update(['status' => 'rechazada']);

                // Reabrir la solicitud de cotización para que Compras
                // pueda registrar nuevas cotizaciones (bucle del flujograma)
                $quote->quoteRequest->update(['status' => 'abierta']);
            }
        });

        if ($purchaseOrder) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', "Compra aprobada. Orden {$purchaseOrder->code} generada exitosamente.");
        }

        return redirect()->route('approvals.index')
            ->with('success', 'Compra rechazada. Compras recibirá la notificación para solicitar nuevas cotizaciones.');
    }
}
