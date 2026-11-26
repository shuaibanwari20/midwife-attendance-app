<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    /** Feature 44 — admin panel language (fa = Dari default, ps = Pashto) from the session. */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('admin_locale', 'fa');
        if (in_array($locale, ['fa', 'ps'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
