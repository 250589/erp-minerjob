<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::with('permissions:id,name')
            ->withCount('users')
            ->get();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }
}