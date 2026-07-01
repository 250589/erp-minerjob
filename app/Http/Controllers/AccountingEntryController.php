<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountingEntryRequest;
use App\Models\AccountingEntry;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\PaymentObligation;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountingEntryController extends Controller
{
    // ─── Formulario de asiento (Paso 14) ─────────────────────

    public function create(Invoice $invoice): Response
    {
        abort_if($invoice->status !== 'validada', 403,
            'Solo se puede registrar el asiento de facturas validadas.'
        );
        abort_if($invoice->accountingEntry()->exists(), 403,
            'Esta factura ya tiene un asiento contable registrado.'
        );

        $invoice->load(['supplier', 'purchaseOrder:id,code', 'items']);

        // Sugerencia automática del asiento (partida doble estándar de compras)
        $suggested = [
            [
                'account_id'  => ChartOfAccount::where('code', '6061')->value('id'),
                'debit'       => $invoice->subtotal,
                'credit'      => 0,
                'description' => "Compra según {$invoice->full_number}",
            ],
            [
                'account_id'  => ChartOfAccount::where('code', '40111')->value('id'),
                'debit'       => $invoice->tax,
                'credit'      => 0,
                'description' => 'IGV por pagar',
            ],
            [
                'account_id'  => ChartOfAccount::where('code', '4212')->value('id'),
                'debit'       => 0,
                'credit'      => $invoice->total,
                'description' => "{$invoice->supplier?->business_name} - {$invoice->full_number}",
            ],
        ];

        return Inertia::render('AccountingEntries/Create', [
            'invoice'          => $invoice->append(['full_number']),
            'accounts'         => ChartOfAccount::active()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type']),
            'suggestedDetails' => $suggested,
        ]);
    }

    // ─── Guardar asiento → Paso 14-15 → crea obligación de pago ─

    public function store(StoreAccountingEntryRequest $request, Invoice $invoice)
    {
        abort_if($invoice->status !== 'validada', 403);

        DB::transaction(function () use ($request, $invoice) {
            $totalDebit  = collect($request->details)->sum('debit');
            $totalCredit = collect($request->details)->sum('credit');

            // Paso 14: Crear asiento contable
            $entry = AccountingEntry::create([
                'invoice_id'   => $invoice->id,
                'entry_number' => AccountingEntry::generateEntryNumber(),
                'entry_date'   => $request->entry_date,
                'description'  => $request->description
                    ?? "Factura {$invoice->full_number} - {$invoice->supplier?->business_name}",
                'total_debit'  => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'status'       => 'confirmado',
                'created_by'   => auth()->id(),
            ]);

            foreach ($request->details as $detail) {
                $entry->details()->create([
                    'account_id'  => $detail['account_id'],
                    'debit'       => $detail['debit'],
                    'credit'      => $detail['credit'],
                    'description' => $detail['description'] ?? null,
                ]);
            }

            // Paso 15: Factura registrada y lista para pago
            $invoice->update(['status' => 'registrada']);

            // ── Paso 16: Crear obligación de pago automáticamente ──
            $invoice->load('purchaseOrder');
            $paymentTermDays = $invoice->purchaseOrder?->payment_term_days ?? 0;

            PaymentObligation::create([
                'invoice_id'          => $invoice->id,
                'accounting_entry_id' => $entry->id,
                'amount'              => $invoice->total,
                'currency'            => $invoice->currency,
                'due_date'            => now()
                    ->addDays($paymentTermDays)
                    ->toDateString(),
                'status'              => 'pendiente',
            ]);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success',
                'Asiento contable registrado. Factura lista para pago y obligación creada en Finanzas. (Pasos 14-16)'
            );
    }
}
