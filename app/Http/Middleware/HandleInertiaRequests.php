<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'area'        => $user->area?->name,
                    'roles'       => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_admin'    => $user->isAdmin(),
                ] : null,
            ],
            'flash' => [
                'success'       => $request->session()->get('success'),
                'error'         => $request->session()->get('error'),
                'warning'       => $request->session()->get('warning'),
                'import_result' => $request->session()->get('import_result'),
            ],
        ]);
    }
}