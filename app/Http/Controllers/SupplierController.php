<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $suppliers = Supplier::withCount('contacts')
            ->when($request->search, fn ($q) =>
                $q->where('business_name', 'like', "%{$request->search}%")
                  ->orWhere('tax_id', 'like', "%{$request->search}%")
                  ->orWhere('trade_name', 'like', "%{$request->search}%")
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(StoreSupplierRequest $request)
    {
        DB::transaction(function () use ($request) {
            $supplier = Supplier::create(
                $request->except('contacts')
            );

            if ($request->filled('contacts')) {
                foreach ($request->contacts as $contact) {
                    $supplier->contacts()->create($contact);
                }
            }
        });

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor registrado exitosamente.');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load('contacts');

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        $supplier->load('contacts');

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        DB::transaction(function () use ($request, $supplier) {
            $supplier->update($request->except('contacts'));

            // Reemplazar contactos
            $supplier->contacts()->delete();
            if ($request->filled('contacts')) {
                foreach ($request->contacts as $contact) {
                    $supplier->contacts()->create($contact);
                }
            }
        });

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor eliminado.');
    }

    // ─── Activar / Desactivar ─────────────────────────────────

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->update([
            'status' => $supplier->status === 'activo' ? 'inactivo' : 'activo',
        ]);

        return back()->with('success', "Proveedor {$supplier->status} exitosamente.");
    }
}
