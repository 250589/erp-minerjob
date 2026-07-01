<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\KardexMovement;
use App\Models\PaymentObligation;
use App\Models\PurchaseOrder;
use App\Models\QuoteRequest;
use App\Models\Requirement;
use App\Models\Stock;
use App\Models\TransferOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // ─── Contadores por módulo ─────────────────────────────────────────
        $stats = [
            'requirements' => [
                'pendiente'     => Requirement::where('status', 'pendiente')->count(),
                'en_cotizacion' => Requirement::where('status', 'en_cotizacion')->count(),
                'total'         => Requirement::count(),
            ],
            'quote_requests' => [
                'abierta' => QuoteRequest::where('status', 'abierta')->count(),
            ],
            'approvals' => [
                'pendiente' => Approval::where('status', 'pendiente')->count(),
            ],
            'purchase_orders' => [
                'enviada'   => PurchaseOrder::where('status', 'enviada')->count(),
                'facturada' => PurchaseOrder::where('status', 'facturada')->count(),
                'pagada'    => PurchaseOrder::where('status', 'pagada')->count(),
            ],
            'invoices' => [
                'recibida'    => Invoice::where('status', 'recibida')->count(),
                'en_revision' => Invoice::where('status', 'en_revision')->count(),
                'validada'    => Invoice::where('status', 'validada')->count(),
            ],
            'payments' => [
                'pendiente'    => PaymentObligation::where('status', 'pendiente')->count(),
                'total_amount' => (float) PaymentObligation::where('status', 'pendiente')->sum('amount'),
                'vencidas'     => PaymentObligation::where('status', 'pendiente')
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()->toDateString())
                    ->count(),
            ],
            'stock' => [
                'bajo_minimo' => DB::table('stocks')
                    ->join('products', 'stocks.product_id', '=', 'products.id')
                    ->whereNull('products.deleted_at')
                    ->where('products.min_stock', '>', 0)
                    ->whereRaw('stocks.quantity <= products.min_stock')
                    ->where('stocks.quantity', '>=', 0)
                    ->distinct()
                    ->count('stocks.product_id'),
                'sin_stock' => Stock::where('quantity', '<=', 0)->count(),
            ],
            'transfers' => [
                'en_transito' => TransferOrder::where('status', 'en_transito')->count(),
                'creada'      => TransferOrder::where('status', 'creada')->count(),
            ],
            'deliveries' => [
                'borrador' => DeliveryNote::where('status', 'borrador')->count(),
            ],
        ];

        // ─── Últimos movimientos del kardex ────────────────────────────────
        $recentKardex = KardexMovement::with([
            'product:id,sku,name',
            'warehouse:id,code,name',
        ])
            ->latest('movement_date')
            ->limit(8)
            ->get(['id', 'warehouse_id', 'product_id', 'movement_type',
                   'quantity', 'unit_cost', 'balance_quantity', 'movement_date']);

        // ─── Aprobaciones pendientes recientes ────────────────────────────
        $pendingApprovals = Approval::with(['requestedBy:id,name'])
            ->where('status', 'pendiente')
            ->latest()
            ->limit(5)
            ->get(['id', 'approvable_type', 'approvable_id',
                   'requested_by', 'created_at']);

        // ─── OCs sin recibir ──────────────────────────────────────────────
        $pendingReceptions = PurchaseOrder::with(['supplier:id,business_name'])
            ->where('status', 'enviada')
            ->latest()
            ->limit(5)
            ->get(['id', 'code', 'supplier_id', 'total', 'created_at']);

        return Inertia::render('Dashboard', [
            'stats'             => $stats,
            'recentKardex'      => $recentKardex,
            'pendingApprovals'  => $pendingApprovals,
            'pendingReceptions' => $pendingReceptions,
        ]);
    }
}