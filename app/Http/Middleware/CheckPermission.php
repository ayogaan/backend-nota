<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        if (Auth::check() && Auth::user()->hasPermissionTo($permission)) {
            return $next($request);
        }

        // If the user does not have the required permission, you can redirect or return an error response.
        return response('Unauthorized', 403);
    }
}
