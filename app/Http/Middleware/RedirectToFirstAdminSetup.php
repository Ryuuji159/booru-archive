<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToFirstAdminSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin*')) {
            return $next($request);
        }

        if ($request->is('admin/setup*')) {
            return $next($request);
        }

        if (User::query()->exists()) {
            return $next($request);
        }

        return redirect()->route('admin.setup');
    }
}
