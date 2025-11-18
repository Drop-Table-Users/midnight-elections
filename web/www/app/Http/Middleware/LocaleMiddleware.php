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

        // Store in session for persistence
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale based on URL structure:
     * - /en/* = English
     * - /* = Slovak (default)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function determineLocale(Request $request): string
    {
        // Get the first segment of the URL path
        $segment = $request->segment(1);

        // If URL starts with /en, use English
        if ($segment === 'en') {
            return 'en';
        }

        // Otherwise use Slovak (default)
        return $this->defaultLocale;
    }
}
