<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/dashboard')->with('error', 'You do not have access to this section.');
        }

        // Admin users with can_tutor=true get access to tutor-scoped routes
        $allowed = in_array($user->role, $roles)
            || (in_array('tutor', $roles) && $user->role === 'admin' && $user->can_tutor);

        if (!$allowed) {
            return redirect('/dashboard')->with('error', 'You do not have access to this section.');
        }

        return $next($request);
    }
}
