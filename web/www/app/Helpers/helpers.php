<?php

use App\Helpers\LocaleHelper;

if (!function_exists('current_locale')) {
    /**
     * Get the current locale
     *
     * @return string
     */
    function current_locale(): string
    {
        return LocaleHelper::currentLocale();
    }
}

if (!function_exists('locale_name')) {
    /**
     * Get the current locale name
     *
     * @return string
     */
    function locale_name(): string
    {
        return LocaleHelper::currentLocaleName();
    }
}

if (!function_exists('available_locales')) {
    /**
     * Get all available locales
     *
     * @return array
     */
    function available_locales(): array
    {
        return LocaleHelper::availableLocales();
    }
}

if (!function_exists('locale_url')) {
    /**
     * Generate URL with locale parameter
     *
     * @param string $locale
     * @param string|null $url
     * @return string
     */
    function locale_url(string $locale, ?string $url = null): string
    {
        return LocaleHelper::urlWithLocale($locale, $url);
    }
}
