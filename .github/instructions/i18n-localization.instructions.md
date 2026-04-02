---
description: "Use when implementing translations, locale switching, date/time formatting, number formatting, browser language detection, localized Blade views, localized notification content, or adding new user-facing strings. Covers the fr-BE/en-GB/nl-BE tri-lingual architecture, Laravel lang directory conventions, and Belgian locale defaults."
applyTo: "lang/**,resources/views/**,app/Http/Middleware/*Locale*"
---
# Internationalization & Localization Rules

## Supported Locales

| Locale | Language | Role | Laravel Config |
|--------|----------|------|----------------|
| `fr` | French (Belgium) | **Default / fallback** | `app.locale = 'fr'` |
| `en` | English (GB) | Secondary | `app.fallback_locale = 'fr'` |
| `nl` | Dutch (Belgium) | Secondary | — |

Use short locale codes (`fr`, `en`, `nl`) as directory names under `lang/`. Map to full IETF tags (`fr-BE`, `en-GB`, `nl-BE`) only at the HTTP boundary (`Accept-Language`, `Content-Language`) and for ICU formatting.

## Directory Structure

```
lang/
├── fr/
│   ├── auth.php
│   ├── pagination.php
│   ├── passwords.php
│   ├── validation.php
│   ├── sessions.php
│   ├── bookings.php
│   ├── notifications.php
│   ├── coaches.php
│   ├── athletes.php
│   ├── admin.php
│   └── common.php
├── en/
│   └── (mirror of fr/)
├── nl/
│   └── (mirror of fr/)
└── fr.json      # (only if needed for single-use strings)
```

### Rules

- **PHP array files** (`lang/{locale}/domain.php`) for structured, reusable keys — this is the primary format
- **JSON files** (`lang/{locale}.json`) only for one-off strings that don't fit a domain group
- Every key in `fr/` **must** exist in `en/` and `nl/` — no partial translations
- Group keys by domain: `sessions.`, `bookings.`, `notifications.`, `coaches.`, `athletes.`, `admin.`, `common.`
- `common.php` holds shared labels: button text, status labels, form field names, error prefixes

## No Hardcoded Strings

**Never** hardcode user-facing text in PHP classes, Blade views, or Livewire components.

```php
// WRONG
return 'Session confirmed';

// CORRECT
return __('sessions.status.confirmed');
```

```blade
{{-- WRONG --}}
<h1>Upcoming Sessions</h1>

{{-- CORRECT --}}
<h1>{{ __('sessions.upcoming_title') }}</h1>
```

### What Counts as User-Facing

- Page titles, headings, labels, placeholders, button text
- Flash messages, toast notifications, validation messages
- Email subjects, bodies, and notification content
- Status labels, badge text, enum display names
- GDPR/privacy/terms pages (full translated Blade views)

### What Doesn't Need Translation

- Log messages, exception internals, debug output
- Database column names, config keys, route names
- Code comments, PHPDoc annotations

## Translation Key Conventions

Use dot-separated, lowercase, snake_case keys:

```php
// lang/fr/sessions.php
return [
    'status' => [
        'draft'     => 'Brouillon',
        'published' => 'Publiée',
        'confirmed' => 'Confirmée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ],
    'create_title'    => 'Créer une séance',
    'edit_title'      => 'Modifier la séance',
    'participants'    => ':count participant|:count participants',
    'capacity_full'   => 'Complet',
    'booking_success' => 'Réservation confirmée pour :session_name',
];
```

### Pluralization

Use Laravel's `trans_choice()` or `__()` with `|` pipe syntax:

```php
trans_choice('sessions.participants', $count, ['count' => $count]);
```

French and English: singular when count = 1, plural otherwise.
Dutch: same rule.

### Parameters

Use `:parameter` placeholders — never concatenate strings:

```php
// WRONG
__('bookings.success') . ' ' . $session->name

// CORRECT
__('bookings.booking_success', ['session_name' => $session->name])
```

## Locale Detection & Switching

### Detection Order

1. Authenticated user: `$user->locale` column (persisted preference)
2. Session value: `session('locale')`
3. `Accept-Language` header (browser detection)
4. Fallback: `fr`

### Middleware

A single `SetLocale` middleware applies the detection order:

```php
// app/Http/Middleware/SetLocale.php
public function handle(Request $request, Closure $next): Response
{
    $locale = $request->user()?->locale
        ?? $request->session()->get('locale')
        ?? $request->getPreferredLanguage(['fr', 'en', 'nl'])
        ?? 'fr';

    App::setLocale($locale);
    Carbon::setLocale($locale);

    return $next($request);
}
```

- Register in the `web` middleware group — not `api` (API uses `Accept-Language` header per request)
- Store `locale` as a `string` column on the `users` table, default `'fr'`

### API Locale

For API requests, resolve locale from the `Accept-Language` header on every request. Never persist to session:

```php
$locale = $request->getPreferredLanguage(['fr', 'en', 'nl']) ?? 'fr';
```

## Date & Time Formatting

### Display Rules

- Use Carbon's `translatedFormat()` — never PHP's raw `date()` or `strftime()`
- Use `<time datetime="...">` HTML element for accessibility and SEO
- Short date: `translatedFormat('j M Y')` → `3 avr. 2026`
- Long date: `translatedFormat('l j F Y')` → `vendredi 3 avril 2026`
- Time: `translatedFormat('H:i')` → `19:00` (24h format, Belgian convention)
- Date + time: `translatedFormat('j M Y à H:i')` → `3 avr. 2026 à 19:00`
- Relative: `diffForHumans()` → `dans 2 heures` / `il y a 3 jours`

```blade
<time datetime="{{ $session->start_time->toIso8601String() }}">
    {{ $session->start_time->translatedFormat('l j F Y à H:i') }}
</time>
```

### Storage Rules

- Store all dates/times as **UTC** in the database
- Convert to `Europe/Brussels` timezone only for display
- Set `app.timezone = 'UTC'` in config
- Use `->setTimezone('Europe/Brussels')` before `translatedFormat()` in views

## Number & Currency Formatting

### Money Display

Money is stored as integer cents in the database. Formatting to locale-aware display happens **only** in Blade views via the `<x-money>` component or a helper:

| Locale | Format | Example |
|--------|--------|---------|
| `fr` | `12,50 €` | Comma decimal, space before `€` |
| `en` | `€12.50` | Dot decimal, symbol prefix |
| `nl` | `€ 12,50` | Comma decimal, symbol prefix with space |

Use `NumberFormatter` (intl extension) for locale-aware formatting:

```php
$formatter = new \NumberFormatter($locale . '_BE', \NumberFormatter::CURRENCY);
$formatter->formatCurrency($cents / 100, 'EUR');
```

### Numeric Display

- Thousands separator: `.` (fr/nl) or `,` (en)
- Decimal separator: `,` (fr/nl) or `.` (en)
- Use `number_format()` or `NumberFormatter` — never hardcode separators

## Enum Display Labels

Backed enums use a `label()` method that pulls from translations:

```php
enum SessionStatus: string
{
    // ...
    public function label(): string
    {
        return __('sessions.status.' . $this->value);
    }
}
```

This keeps the enum definition clean and the labels translatable.

## Validation Messages

- Override default Laravel validation messages per locale in `lang/{locale}/validation.php`
- Use custom attribute names in `lang/{locale}/validation.php` under the `'attributes'` key
- Form Request classes reference translation keys for custom messages:

```php
public function messages(): array
{
    return [
        'price.min' => __('validation.custom.price.min'),
    ];
}
```

## Notification Localization

Notifications must be sent in the **recipient's** locale, not the sender's or the app's current locale:

```php
$user->notify(
    (new BookingConfirmedNotification($booking))->locale($user->locale)
);
```

- All notification text comes from `lang/{locale}/notifications.php`
- Email subjects, bodies, and action buttons are all translated
- See the `notification-system` instruction for the full event-listener architecture

## Static Pages

Translatable static pages (GDPR, Privacy, Terms) use dedicated Blade views per locale:

```
resources/views/pages/privacy/fr.blade.php
resources/views/pages/privacy/en.blade.php
resources/views/pages/privacy/nl.blade.php
```

Resolve in the controller:

```php
return view('pages.privacy.' . App::getLocale());
```

## Testing

- **Locale switching test**: set each locale, verify translated output in response
- **Completeness test**: assert every key in `lang/fr/` exists in `lang/en/` and `lang/nl/`
- **Date format test**: set locale to `fr`, verify `translatedFormat()` produces French output
- **Fallback test**: request with unsupported `Accept-Language`, verify `fr` is used
- **Parameter substitution test**: verify `:param` placeholders are replaced correctly
- **Pluralization test**: verify singular/plural with counts 0, 1, 2

```php
it('displays session status in French', function () {
    App::setLocale('fr');
    expect(SessionStatus::Confirmed->label())->toBe('Confirmée');
});

it('falls back to French for unsupported locale', function () {
    $this->get('/', ['Accept-Language' => 'de-DE'])
        ->assertSee(__('common.welcome', [], 'fr'));
});
```
