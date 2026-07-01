<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequirementRequest;
use App\Http\Requests\UpdateRequirementRequest;
use App\Models\Area;
use App\Models\Product;
use App\Models\Requirement;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RequirementController extends Controller
{
    // ─── Listado con filtros y paginación ─────────────────────

    public function index(Request $request): Response
    {
        $requirements = Requirement::with(['requester:id,name', 'area:id,name'])
            ->when($request->search, fn ($q) =>
                $q->where('code', 'like', "%{$request->search}%")
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Agregar accessors de estado a cada elemento
        $requirements->through(fn ($r) => $r->append(['status_label', 'status_color']));

        return Inertia::render('Requirements/Index', [
            'requirements' => $requirements,
            'filters'      => $request->only(['search', 'status']),
        ]);
    }

    // ─── Formulario de creación ───────────────────────────────

    public function create(): Response
    {
        return Inertia::render('Requirements/Create', [
            'products' => Product::active()->get(['id', 'sku', 'name']),
            'units'    => UnitOfMeasure::all(['id', 'name', 'abbreviation']),
            'areas'    => Area::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ─── Guardar nuevo requerimiento ──────────────────────────

    public function store(StoreRequirementRequest $request)
    {
        DB::transaction(function () use ($request) {
            $requirement = Requirement::create([
                'requester_id'  => auth()->id(),
                'area_id'       => $request->area_id,
                'code'          => Requirement::generateCode(),
                'justification' => $request->justification,
                'required_date' => $request->required_date,
                'status'        => 'pendiente',
            ]);

            foreach ($request->items as $item) {
                $requirement->items()->create($item);
            }
        });

        return redirect()->route('requirements.index')
            ->with('success', 'Requerimiento creado exitosamente.');
    }

    // ─── Vista detalle ────────────────────────────────────────

    public function show(Requirement $requirement): Response
    {
        $requirement->load([
            'requester:id,name',
            'area:id,name',
            'items.product:id,sku,name',
            'items.unit:id,name,abbreviation',
        ]);

        return Inertia::render('Requirements/Show', [
            'requirement' => $requirement->append(['status_label', 'status_color']),
        ]);
    }

    // ─── Formulario de edición ────────────────────────────────

    public function edit(Requirement $requirement): Response
    {
        abort_if(
            ! in_array($requirement->status, ['pendiente', 'rechazado']),
            403,
            'Este requerimiento no puede editarse en su estado actual.'
        );

        $requirement->load('items');

        return Inertia::render('Requirements/Edit', [
            'requirement' => $requirement->append(['status_label', 'status_color']),
            'products'    => Product::active()->get(['id', 'sku', 'name']),
            'units'       => UnitOfMeasure::all(['id', 'name', 'abbreviation']),
            'areas'       => Area::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ─── Actualizar requerimiento ─────────────────────────────

    public function update(UpdateRequirementRequest $request, Requirement $requirement)
    {
        abort_if(
            ! in_array($requirement->status, ['pendiente', 'rechazado']),
            403
        );

        DB::transaction(function () use ($request, $requirement) {
            $requirement->update(
                $request->only(['area_id', 'justification', 'required_date'])
            );

            // Reemplazar ítems: borrar los anteriores y crear los nuevos
            $requirement->items()->delete();
            foreach ($request->items as $item) {
                $requirement->items()->create($item);
            }
        });

        return redirect()->route('requirements.show', $requirement)
            ->with('success', 'Requerimiento actualizado exitosamente.');
    }

    // ─── Eliminar requerimiento ───────────────────────────────

    public function destroy(Requirement $requirement)
    {
        abort_if(
            $requirement->status !== 'pendiente',
            403,
            'Solo se pueden eliminar requerimientos en estado Pendiente.'
        );

        $requirement->delete();

        return redirect()->route('requirements.index')
            ->with('success', 'Requerimiento eliminado.');
    }

    // ─── Enviar a Compras (paso 2 del flujograma) ─────────────

    public function sendToCompras(Requirement $requirement)
    {
        abort_if(
            $requirement->status !== 'pendiente',
            403,
            'Solo se pueden enviar a Compras los requerimientos en estado Pendiente.'
        );

        $requirement->update(['status' => 'en_cotizacion']);

        return redirect()->route('requirements.show', $requirement)
            ->with('success', 'Requerimiento enviado a Compras. Se iniciará el proceso de cotización.');
    }
}
