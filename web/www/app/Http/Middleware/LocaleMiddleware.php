<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Available languages
     *
     * @var array
     */
    protected $availableLocales = ['en', 'sk'];

    /**
     * Default language (Slovak)
     *
     * @var string
     */
    protected $defaultLocale = 'sk';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Set the application locale
        App::setLocale($locale);

        // Store in session for persistence (use appropriate session key)
        $sessionKey = $this->isAdminRoute($request) ? 'admin_locale' : 'locale';
        Session::put($sessionKey, $locale);

        return $next($request);
    }

    /**
     * Check if the current request is for an admin route
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isAdminRoute(Request $request): bool
    {
        return $request->is('admin/*') || $request->is('admin');
    }

    /**
     * Determine the locale based on:
     * - Admin routes: Use admin_locale session
     * - Public routes: Use URL structure (/en/* = English, /* = Slovak)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function determineLocale(Request $request): string
    {
        // For admin routes, check admin_locale session first
        if ($this->isAdminRoute($request)) {
            $adminLocale = Session::get('admin_locale');
            if ($adminLocale && in_array($adminLocale, $this->availableLocales)) {
                return $adminLocale;
            }
        }

        // For public routes, check URL structure
        $segment = $request->segment(1);

        // If URL starts with /en, use English
        if ($segment === 'en') {
            return 'en';
        }

        // Check regular locale session
        $sessionLocale = Session::get('locale');
        if ($sessionLocale && in_array($sessionLocale, $this->availableLocales)) {
            return $sessionLocale;
        }

        // Otherwise use Slovak (default)
        return $this->defaultLocale;
    }
}
