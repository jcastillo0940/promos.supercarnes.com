<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->canAccessModule($module)) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'No tienes permisos para realizar esta acción.',
                ], 403);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
