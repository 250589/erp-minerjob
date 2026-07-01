<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::with(['roles:id,name', 'area:id,name'])
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when($request->role, fn ($q) =>
                $q->whereHas('roles', fn ($r) => $r->where('name', $request->role))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'roles'   => Role::all(['id', 'name']),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::all(['id', 'name']),
            'areas' => Area::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'area_id'  => $request->area_id,
            'phone'    => $request->phone,
            'status'   => 'activo',
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', "Usuario {$user->name} creado con rol {$request->role}.");
    }

    public function edit(User $user): Response
    {
        $user->load(['roles:id,name', 'area:id,name']);

        return Inertia::render('Users/Edit', [
            'user'  => $user,
            'roles' => Role::all(['id', 'name']),
            'areas' => Area::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update([
            'name'    => $request->name,
            'email'   => $request->email,
            'area_id' => $request->area_id,
            'phone'   => $request->phone,
            'status'  => $request->status,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', "Usuario {$user->name} actualizado.");
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'No puedes eliminar tu propio usuario.');
        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }
}