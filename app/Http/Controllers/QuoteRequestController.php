<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestRequest;
use App\Models\QuoteRequest;
use App\Models\Requirement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuoteRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $quoteRequests = QuoteRequest::with(['requirement:id,code', 'createdBy:id,name'])
            ->withCount(['suppliers', 'quotes'])
            ->when($request->search, fn ($q) =>
                $q->where('code', 'like', "%{$request->search}%")
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $quoteRequests->through(
            fn ($qr) => $qr->append(['status_label', 'status_color'])
        );

        return Inertia::render('QuoteRequests/Index', [
            'quoteRequests' => $quoteRequests,
            'filters'       => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('QuoteRequests/Create', [
            'requirements' => Requirement::where('status', 'en_cotizacion')
                ->get(['id', 'code', 'justification']),
            'suppliers'    => Supplier::active()->get(['id', 'business_name', 'trade_name', 'tax_id']),
            // Permite pre-seleccionar desde la vista de requerimiento
            'preselectedRequirementId' => $request->integer('requirement_id') ?: null,
        ]);
    }

    public function store(StoreQuoteRequestRequest $request)
    {
        DB::transaction(function () use ($request) {
            $quoteRequest = QuoteRequest::create([
                'requirement_id' => $request->requirement_id,
                'created_by'     => auth()->id(),
                'code'           => QuoteRequest::generateCode(),
                'sent_date'      => now()->toDateString(),
                'deadline_date'  => $request->deadline_date,
                'status'         => 'abierta',
            ]);

            foreach ($request->supplier_ids as $supplierId) {
                $quoteRequest->suppliers()->create([
                    'supplier_id' => $supplierId,
                    'sent_at'     => now(),
                    'status'      => 'pendiente',
                ]);
            }
        });

        return redirect()->route('quote-requests.index')
            ->with('success', 'Solicitud de cotización creada y enviada a proveedores.');
    }

    public function show(QuoteRequest $quoteRequest): Response
    {
        $quoteRequest->load([
            'requirement:id,code,justification',
            'requirement.items.unit:id,name,abbreviation',
            'suppliers.supplier:id,business_name,trade_name,tax_id',
            'quotes.supplier:id,business_name',
            'quotes.items',
            'comparison.selectedQuote',
        ]);

        return Inertia::render('QuoteRequests/Show', [
            'quoteRequest' => $quoteRequest->append(['status_label', 'status_color']),
        ]);
    }

    // Cerrar solicitud sin seleccionar ganador (paso cancelación)
    public function close(QuoteRequest $quoteRequest)
    {
        abort_if($quoteRequest->status !== 'abierta', 403);
        $quoteRequest->update(['status' => 'cerrada']);
        return back()->with('success', 'Solicitud de cotización cerrada.');
    }
}
