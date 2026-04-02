---
description: "Scaffold a complete set of lang/{locale}/domain.php translation files from a list of keys and French values, auto-generating English and Dutch placeholders"
agent: "agent"
argument-hint: "Domain name and key-value list, e.g. 'sessions: create_title=Créer une séance, edit_title=Modifier la séance'"
tools: [search, read, editFile, createFile]
---

# Scaffold Translation Batch

Generate a complete set of `lang/fr/`, `lang/en/`, and `lang/nl/` translation files for a given domain from user-provided French keys and values.

## Project Context

- Three locales: `fr` (default), `en`, `nl` — see [i18n-localization.instructions.md](../instructions/i18n-localization.instructions.md).
- PHP array files under `lang/{locale}/domain.php` — this is the **only** format.
- Every key in `fr/` **must** exist in `en/` and `nl/` — no partial translations.
- Keys are dot-separated, lowercase, snake_case.
- Use `:parameter` placeholders — never string concatenation.
- Use `|` pipe syntax for pluralization: `:count participant|:count participants`.
- `declare(strict_types=1)` is **not** used in lang files (they return plain arrays).

## Input Parsing

The user provides input in one of these formats:

### Format A: Inline key=value pairs
```
sessions: create_title=Créer une séance, edit_title=Modifier la séance
```

### Format B: Nested YAML-like list
```
domain: bookings
keys:
  - status.pending = En attente
  - status.confirmed = Confirmée
  - status.cancelled = Annulée
  - success_message = Réservation confirmée pour :session_name
  - cancel_confirm = Voulez-vous annuler cette réservation ?
```

### Format C: Just a domain name
```
bookings
```
When only a domain name is given:
1. Read the related model, service, and Blade views to infer what keys are needed.
2. Propose a key list to the user for approval before generating.

## Generation Steps

### 1. Check Existing Files

Read `lang/fr/{domain}.php`, `lang/en/{domain}.php`, `lang/nl/{domain}.php` if they exist. New keys must be **merged** into existing files — never overwrite existing translations.

### 2. Generate French File

Create or update `lang/fr/{domain}.php` with the user-provided values:

```php
<?php

return [
    'create_title' => 'Créer une séance',
    'edit_title'   => 'Modifier la séance',
    'status' => [
        'draft'     => 'Brouillon',
        'published' => 'Publiée',
        'confirmed' => 'Confirmée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ],
    'participants' => ':count participant|:count participants',
];
```

### 3. Generate English File

Translate each French value to English. Use natural, native-sounding translations — not literal word-for-word:

```php
<?php

return [
    'create_title' => 'Create a session',
    'edit_title'   => 'Edit session',
    'status' => [
        'draft'     => 'Draft',
        'published' => 'Published',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'participants' => ':count participant|:count participants',
];
```

### 4. Generate Dutch File

Translate each French value to Dutch (Belgian Dutch). Use natural Belgian Dutch phrasing:

```php
<?php

return [
    'create_title' => 'Sessie aanmaken',
    'edit_title'   => 'Sessie bewerken',
    'status' => [
        'draft'     => 'Ontwerp',
        'published' => 'Gepubliceerd',
        'confirmed' => 'Bevestigd',
        'completed' => 'Voltooid',
        'cancelled' => 'Geannuleerd',
    ],
    'participants' => ':count deelnemer|:count deelnemers',
];
```

## Translation Rules

### Preserve Exactly
- `:parameter` placeholders — same names, same positions
- `|` pipe pluralization markers — same count of variants
- Array nesting structure — identical key hierarchy across all 3 files
- Key names — never translate keys, only values

### Translate With Care
- **Formal register** — use `vous` (fr), formal "you" (en), `u` (nl) for user-facing text
- **Belgian Dutch** — use Belgian conventions, not Netherlands Dutch where they differ (e.g. `gsm` not `mobiel`)
- **Gendered forms** — French uses feminine for `séance` (une séance confirmée), match grammatical gender
- **Pluralization** — French/English: singular at 1, plural otherwise. Dutch: same rule

### Status & Enum Labels
When translating status values or enum labels, match the past participle form convention:
- French: feminine past participle if the noun is feminine (`Confirmée` for séance)
- English: past participle (`Confirmed`)
- Dutch: past participle (`Bevestigd`)

## Nested Key Handling

Dot-separated keys in user input become nested arrays:

```
# Input
status.draft = Brouillon
status.published = Publiée

# Output
'status' => [
    'draft'     => 'Brouillon',
    'published' => 'Publiée',
],
```

## After Generation

1. **Verify key parity** — assert `fr/`, `en/`, `nl/` files have identical key structures.
2. **Check placeholder parity** — every `:param` in `fr` must appear in `en` and `nl`.
3. **List new keys** — show the user a summary table of all generated keys with values in all 3 locales.
4. **Suggest a Pest test** — propose a test that asserts key completeness across all 3 locale files:

```php
it('has all translation keys in every locale', function () {
    $fr = require lang_path('fr/{domain}.php');
    $en = require lang_path('en/{domain}.php');
    $nl = require lang_path('nl/{domain}.php');

    $frKeys = array_keys_dot($fr);
    $enKeys = array_keys_dot($en);
    $nlKeys = array_keys_dot($nl);

    expect($enKeys)->toEqual($frKeys);
    expect($nlKeys)->toEqual($frKeys);
});
```

## Output Format

Present results as a summary table followed by the 3 files:

| Key | French | English | Dutch |
|-----|--------|---------|-------|
| `create_title` | Créer une séance | Create a session | Sessie aanmaken |
| `status.draft` | Brouillon | Draft | Ontwerp |
| ... | ... | ... | ... |

Then create/update the 3 files.
