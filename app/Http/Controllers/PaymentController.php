<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\PaymentObligation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    // ─── Listado de obligaciones de pago (Paso 16) ────────────

    public function index(Request $request): Response
    {
        $status = $request->status ?? 'pendiente';

        $obligations = PaymentObligation::with([
            'invoice.supplier:id,business_name',
            'invoice.purchaseOrder:id,code',
            'payments.voucher',
        ])
            ->when($status !== 'todos', fn ($q) => $q->where('status', $status))
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        $obligations->through(
            fn ($o) => $o->append(['status_label', 'status_color'])
        );

        return Inertia::render('Payments/Index', [
            'obligations'  => $obligations,
            'filters'      => ['status' => $status],
            'counts'       => [
                'pendiente' => PaymentObligation::where('status', 'pendiente')->count(),
                'pagado'    => PaymentObligation::where('status', 'pagado')->count(),
            ],
            'totalPending' => PaymentObligation::where('status', 'pendiente')->sum('amount'),
        ]);
    }

    // ─── Detalle de la obligación ─────────────────────────────

    public function show(PaymentObligation $obligation): Response
    {
        $obligation->load([
            'invoice.supplier',
            'invoice.purchaseOrder:id,code,payment_term_days',
            'accountingEntry:id,entry_number',
            'payments.voucher',
            'payments.createdBy:id,name',
        ]);

        return Inertia::render('Payments/Show', [
            'obligation' => $obligation->append(['status_label', 'status_color']),
        ]);
    }

    // ─── Formulario de pago (Paso 17) ────────────────────────

    public function create(PaymentObligation $obligation): Response
    {
        abort_if($obligation->status === 'pagado', 403, 'Esta obligación ya fue pagada.');

        $obligation->load([
            'invoice.supplier',
            'invoice.purchaseOrder:id,code',
        ]);

        return Inertia::render('Payments/Register', [
            'obligation' => $obligation->append(['status_label', 'status_color']),
        ]);
    }

    // ─── Registrar pago y voucher (Pasos 17-18) ───────────────

    public function store(StorePaymentRequest $request, PaymentObligation $obligation)
    {
        abort_if($obligation->status === 'pagado', 403, 'Esta obligación ya fue pagada.');

        DB::transaction(function () use ($request, $obligation) {
            $payment = $obligation->payments()->create([
                'payment_date'        => $request->payment_date,
                'method'              => $request->method,
                'amount'              => $request->amount,
                'currency'            => $request->currency,
                'exchange_rate'       => $request->exchange_rate,
                'origin_account'      => $request->origin_account,
                'destination_account' => $request->destination_account,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
                'status'              => 'registrado',
                'created_by'          => auth()->id(),
            ]);

            // Paso 18: guardar el voucher si se adjuntó
            if ($request->hasFile('voucher_file')) {
                $file = $request->file('voucher_file');
                $path = $file->store('vouchers', 'public');

                $payment->voucher()->create([
                    'file_name'   => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                ]);
            }
        });

        return redirect()->route('payments.show', $obligation)
            ->with('success', 'Pago registrado exitosamente. (Pasos 17-18)');
    }

    // ─── Confirmar pago → OC y Factura PAGADAS (Paso 19) ─────

    public function confirm(PaymentObligation $obligation)
    {
        $payment = $obligation->payments()
            ->where('status', 'registrado')
            ->latest('created_at')
            ->first();

        abort_if(!$payment, 404, 'No hay pagos en estado registrado para confirmar.');

        DB::transaction(function () use ($obligation, $payment) {
            // Confirmar el pago
            $payment->update(['status' => 'confirmado']);

            // Marcar obligación como pagada
            $obligation->update(['status' => 'pagado']);

            // Marcar factura como pagada
            $invoice = $obligation->invoice;
            $invoice->update(['status' => 'pagada']);

            // Paso 19: Actualizar la OC a PAGADA
            $invoice->purchaseOrder->update(['status' => 'pagada']);
        });

        return back()->with(
            'success',
            'Pago confirmado. Orden de Compra marcada como PAGADA. ✓ (Paso 19)'
        );
    }

    // ─── Subir voucher por separado (si no se adjuntó al registrar) ──

    public function uploadVoucher(Request $request, Payment $payment)
    {
        $request->validate([
            'voucher_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Eliminar voucher anterior si existe
        if ($payment->voucher) {
            Storage::disk('public')->delete($payment->voucher->file_path);
            $payment->voucher()->delete();
        }

        $file = $request->file('voucher_file');
        $path = $file->store('vouchers', 'public');

        $payment->voucher()->create([
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'mime_type'   => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Voucher subido exitosamente. (Paso 18)');
    }
}
