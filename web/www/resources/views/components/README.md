# Laravel Localization Components

## Language Switcher

To use the language switcher in your views:

```blade
<x-language-switcher />
```

## Translation Keys Usage

### In Blade Templates

Use the `__()` helper function or `@lang` directive:

```blade
<!-- Simple translation -->
<h1>{{ __('messages.welcome') }}</h1>

<!-- Translation with variables -->
<p>{{ __('elections.transaction.preparing', ['action' => 'vote']) }}</p>

<!-- Using @lang directive -->
<p>@lang('messages.btn.submit')</p>
```

### In Controllers

```php
// Simple translation
$message = __('validation.success.vote_cast');

// Translation with variables
$message = __('elections.transaction.failed', ['action' => 'register', 'message' => $error]);
```

### Available Translation Files

- `resources/lang/en/messages.php` - General UI text (English)
- `resources/lang/sk/messages.php` - General UI text (Slovak)
- `resources/lang/en/elections.php` - Election-specific text (English)
- `resources/lang/sk/elections.php` - Election-specific text (Slovak)
- `resources/lang/en/validation.php` - Validation messages (English)
- `resources/lang/sk/validation.php` - Validation messages (Slovak)

### Language Switching

Users can switch languages by:

1. Query parameter: `?lang=sk` or `?lang=en`
2. Using the language switcher component
3. Route: `/locale/{locale}`

The selected language is stored in the session.

### Helper Functions

```php
// Get current locale code (e.g., 'en', 'sk')
$locale = current_locale();

// Get current locale name (e.g., 'English', 'Slovenčina')
$name = locale_name();

// Get all available locales
$locales = available_locales(); // ['en' => 'English', 'sk' => 'Slovenčina']

// Generate URL with locale
$url = locale_url('sk', '/elections/1'); // Adds ?lang=sk to URL
```
