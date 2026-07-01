<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::with(['category:id,name', 'unit:id,abbreviation'])
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
            )
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $products->through(fn ($p) => $p->append(['status_color', 'status_label']));

        return Inertia::render('Products/Index', [
            'products'   => $products,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'filters'    => $request->only(['search', 'status', 'category_id']),
            'counts'     => [
                'activo'   => Product::where('status', 'activo')->count(),
                'inactivo' => Product::where('status', 'inactivo')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'units'      => UnitOfMeasure::orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());
        return redirect()->route('products.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product'    => $product->load('category:id,name')->append(['status_color', 'status_label']),
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'units'      => UnitOfMeasure::orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return back()->with('success', 'Producto eliminado.');
    }
}