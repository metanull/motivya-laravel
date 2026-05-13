---
description: "Use when working on maps, geocoding, address validation, directions links, map provider configuration, map JavaScript, provider health checks, or admin readiness checks for mapping APIs. Enforces Google-or-free-provider selection, no mixed providers, and no silent fallback."
applyTo: "config/maps.php,app/Services/*Map*,app/Services/*Geocod*,app/Services/*Address*,app/Livewire/**/Readiness*.php,app/Livewire/Session/**,resources/js/*map*.js,resources/views/**/session*.blade.php,resources/views/components/*map*.blade.php,tests/**/*Map*,tests/**/*Geocod*,tests/**/*Address*,doc/**/*map*,doc/**/*geolocation*"
---
# Mapping Provider Rules - Motivya

Motivya uses one coherent mapping provider stack per runtime configuration.

## Provider Selection

- Google Maps Platform is selected when `GOOGLE_MAPS_API_KEY` is configured and valid enough to be used.
- The free-service alternative is selected only when `GOOGLE_MAPS_API_KEY` is absent.
- Provider selection is a hard configuration-derived decision. Do not add per-feature switches such as one provider for tiles and another for geocoding in the same runtime.
- Do not silently fall back from Google to the free provider, or from the free provider to Google, after an API error. The selected provider must fail visibly and be reported through logs/readiness checks.

## Capability Coverage

The selected provider stack must own all mapping capabilities:

- Map display/rendering.
- Address validation and geocoding.
- Discovery search coordinate resolution.
- Directions URL generation.
- Admin readiness and health probes.

## Implementation Shape

- Centralize provider selection and capability calls in services under `app/Services/`; Livewire components and Blade views should consume provider-neutral DTOs or view models.
- Do not hardcode provider URLs, tile style URLs, JavaScript loader URLs, API keys, or fallback behavior in Blade or feature-specific JavaScript.
- Health checks must verify every required capability for the selected provider and report actionable failures.
- Tests must cover Google-selected behavior, free-provider-selected behavior, and configured-provider failure behavior.
