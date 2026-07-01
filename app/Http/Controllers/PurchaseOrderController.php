<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $purchaseOrders = PurchaseOrder::with([
            'supplier:id,business_name',
            'requirement:id,code',
            'createdBy:id,name',
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

        $purchaseOrders->through(
            fn ($po) => $po->append(['status_label', 'status_color'])
        );

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'filters'        => $request->only(['search', 'status']),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'supplier',
            'requirement:id,code',
            'items.product:id,sku,name',
            'approvedBy:id,name',
            'createdBy:id,name',
        ]);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder->append(['status_label', 'status_color']),
        ]);
    }

    /** Paso 9: Compras envía la OC al proveedor */
    public function send(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status !== 'generada', 403);
        $purchaseOrder->update(['status' => 'enviada']);
        return back()->with('success', 'Orden de Compra enviada al proveedor. (Paso 9)');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status === 'pagada', 403, 'No se puede anular una OC pagada.');
        $purchaseOrder->update(['status' => 'anulada']);
        return redirect()->route('purchase-orders.index')
            ->with('success', 'Orden de Compra anulada.');
    }
}
