<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleHelper
{
    /**
     * Available languages
     *
     * @var array
     */
    public static $availableLocales = [
        'en' => 'English',
        'sk' => 'Slovenčina',
    ];

    /**
     * Get the current locale
     *
     * @return string
     */
    public static function currentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Get the current locale name
     *
     * @return string
     */
    public static function currentLocaleName(): string
    {
        $locale = self::currentLocale();
        return self::$availableLocales[$locale] ?? 'English';
    }

    /**
     * Get all available locales
     *
     * @return array
     */
    public static function availableLocales(): array
    {
        return self::$availableLocales;
    }

    /**
     * Switch to a specific locale
     *
     * @param string $locale
     * @return bool
     */
    public static function switchLocale(string $locale): bool
    {
        if (!array_key_exists($locale, self::$availableLocales)) {
            return false;
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return true;
    }

    /**
     * Generate URL with locale parameter
     *
     * @param string $locale
     * @param string|null $url
     * @return string
     */
    public static function urlWithLocale(string $locale, ?string $url = null): string
    {
        $url = $url ?: url()->current();

        // Parse URL and add/update lang parameter
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        $queryParams['lang'] = $locale;

        $newQuery = http_build_query($queryParams);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        if (isset($parsedUrl['port'])) {
            $baseUrl .= ':' . $parsedUrl['port'];
        }

        $baseUrl .= $parsedUrl['path'] ?? '/';

        return $baseUrl . ($newQuery ? '?' . $newQuery : '');
    }
}
