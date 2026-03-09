<?php

namespace Webkul\Security\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents redirect loop: /admin -> /admin/login -> /admin -> ...
 * When the browser hits the login page with an existing session, the default Filament
 * Login redirects "already logged in" users to /admin. If the next request to /admin
 * is not considered authenticated (e.g. session/cookie issue), the panel redirects
 * back to login, causing a loop. We break it by always showing the login form when
 * the user lands on GET /admin/login: we clear the auth state so the Login page
 * never redirects away and the user can sign in again.
 */
class EnsureAdminLoginNoRedirectLoop
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminLogin = $request->isMethod('GET')
            && ($request->path() === 'admin/login' || $request->routeIs('filament.admin.auth.login'));

        // Always clear auth when hitting the login page. Stops the loop where login
        // redirects to /admin but the next /admin request doesn't see the session.
        if ($isAdminLogin && Filament::auth()->check()) {
            Filament::auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}
