<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is an admin route and use admin_locale if available
        if ($request->is('admin/*') || $request->is('admin')) {
            $locale = session('admin_locale', session('locale', config('app.locale', 'en')));
        } else {
            $locale = session('locale', config('app.locale', 'en'));
        }

        if (!in_array($locale, ['en', 'sk'])) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
