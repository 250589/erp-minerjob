<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $stocks = Stock::with([
            'warehouse:id,code,name,type',
            'product:id,sku,name,min_stock,current_sale_price',
            'product.unit:id,abbreviation',
        ])
            ->when($request->warehouse_id, fn ($q) =>
                $q->where('warehouse_id', $request->warehouse_id)
            )
            ->when($request->search, fn ($q) =>
                $q->whereHas('product', fn ($p) =>
                    $p->where('name', 'like', "%{$request->search}%")
                      ->orWhere('sku', 'like', "%{$request->search}%")
                )
            )
            ->where('quantity', '>', 0)
            ->orderBy('warehouse_id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Stock/Index', [
            'stocks'     => $stocks,
            'warehouses' => Warehouse::active()->get(['id', 'code', 'name', 'type']),
            'filters'    => $request->only(['warehouse_id', 'search']),
            'totals'     => [
                'products' => $stocks->total(),
                'value'    => Stock::sum(\DB::raw('quantity * average_cost')),
            ],
        ]);
    }
}
