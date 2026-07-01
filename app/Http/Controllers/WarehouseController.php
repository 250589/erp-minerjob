<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $warehouses = Warehouse::with(['parent:id,name', 'manager:id,name'])
            ->withCount('stocks')
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
            )
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $warehouses->through(fn ($w) => $w->append(['type_color', 'type_label', 'status_color']));

        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses,
            'filters'    => $request->only(['search', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Warehouses/Create', [
            'parents' => Warehouse::active()->where('type', 'principal')
                ->get(['id', 'code', 'name']),
            'users'   => User::where('status', 'activo')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreWarehouseRequest $request)
    {
        Warehouse::create($request->validated());
        return redirect()->route('warehouses.index')
            ->with('success', 'Almacén creado exitosamente.');
    }

    public function edit(Warehouse $warehouse): Response
    {
        return Inertia::render('Warehouses/Edit', [
            'warehouse' => $warehouse->load(['parent:id,name', 'manager:id,name'])
                ->append(['type_color', 'type_label', 'status_color']),
            'parents'   => Warehouse::active()->where('type', 'principal')
                ->where('id', '!=', $warehouse->id)
                ->get(['id', 'code', 'name']),
            'users'     => User::where('status', 'activo')->get(['id', 'name']),
        ]);
    }

    public function update(StoreWarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->update($request->validated());
        return redirect()->route('warehouses.index')
            ->with('success', 'Almacén actualizado.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->update(['status' => 'inactivo']);
        return back()->with('success', 'Almacén desactivado.');
    }
}