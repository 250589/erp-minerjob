<?php

namespace App\Http\Controllers;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KardexController extends Controller
{
    public function index(Request $request): Response
    {
        $movements = KardexMovement::with([
            'warehouse:id,code,name',
            'product:id,sku,name',
            'createdBy:id,name',
        ])
            ->when($request->warehouse_id, fn ($q) =>
                $q->where('warehouse_id', $request->warehouse_id)
            )
            ->when($request->product_id, fn ($q) =>
                $q->where('product_id', $request->product_id)
            )
            ->when($request->date_from, fn ($q) =>
                $q->whereDate('movement_date', '>=', $request->date_from)
            )
            ->when($request->date_to, fn ($q) =>
                $q->whereDate('movement_date', '<=', $request->date_to)
            )
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Kardex/Index', [
            'movements'  => $movements,
            'warehouses' => Warehouse::active()->get(['id', 'code', 'name']),
            'products'   => Product::active()
                ->when($request->warehouse_id, fn ($q) =>
                    $q->whereHas('stocks', fn ($s) =>
                        $s->where('warehouse_id', $request->warehouse_id)
                          ->where('quantity', '>', 0)
                    )
                )
                ->get(['id', 'sku', 'name']),
            'filters'    => $request->only([
                'warehouse_id', 'product_id', 'date_from', 'date_to',
            ]),
        ]);
    }
}
