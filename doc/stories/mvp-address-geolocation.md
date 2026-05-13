# MVP Address and Geolocation Precision

## Epic

Title: Deliver Precise Validated Addresses for MVP Search, Sessions, and Directions

Context: Motivya already has map rendering, directions links, `postal_code_coordinates`, `geocoding_cache`, `GeocodingService`, and distance-based session search. Local review shows session creation and editing still use split `location` and `postalCode` fields, `SessionService` still resolves coordinates through `PostalCodeCoordinateService`, and typed discovery searches only resolve postal codes or municipalities. Coach application/profile address data is also postal-code-only. OVH production has the required migrations (`sport_sessions`, `postal_code_coordinates`, `geocoding_cache`) and a configured Google Maps key, but `sport_sessions` stores only `location`, `postal_code`, `latitude`, and `longitude`; production currently has 9 sessions, 3 distinct session postal codes, 8 sessions with coordinates, 1 session missing coordinates, 35 postal-code reference rows, and 0 geocoding-cache rows. Existing coordinates therefore represent postal-code centers, not validated street addresses.

Problem: The MVP cannot support user expectations for precise address entry, exact session pins, exact directions, or address-based search. The current implementation validates postal-code syntax instead of validating an address against the active maps/geocoding provider, and it does not persist a normalized address result or provider metadata.

Solution: Add provider-backed address validation, store normalized address data and exact coordinates for every address-bearing workflow, replace split address inputs with one validated address field, update search and directions to use precise coordinates, and provide a production-safe backfill path for existing rows.

Implementation Instructions:

1. Implement the child stories below in order.
2. Follow ADR-016: when `GOOGLE_MAPS_API_KEY` is configured, the Google Maps Platform stack must own map display, address validation/geocoding, discovery coordinate resolution, directions, and health checks. When the key is absent, the configured free-service stack must own all of those capabilities. Do not mix providers in the same runtime.
3. Use Laravel services for provider calls and normalization; keep Livewire components thin and use Form Objects.
4. Use localized strings in `lang/fr`, `lang/en`, and `lang/nl` for all user-visible labels, placeholders, validation errors, and readiness messages.
5. Do not call an external geocoder on every keystroke. Resolve only when the user selects a suggestion, submits a search, or triggers an explicit validation action with debounce and cache.

Definition of Done:

- Every child story below is completed in dependency order.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless a child story explicitly requires them.

## Story 1

Title: Add Provider-Backed Address Validation Service

Context: `app/Services/GeocodingService.php` currently resolves postal codes/municipalities locally and falls back to Google coordinates. It returns only latitude and longitude, and the cache table stores only query/provider/coordinates/found. The MVP requires a single address field that is validated through the active mapping provider selected by ADR-016. Google and the free-service alternative must both work as complete provider stacks, but only one may be active at runtime.

Problem: There is no service contract that can validate a full street address, normalize the selected result, enforce Belgian address constraints, expose postal code/city/country components, or preserve provider metadata for storage and auditing.

Solution: Introduce a reusable address validation layer that returns a normalized `ValidatedAddress` result through the active mapping provider, with cached provider responses and deterministic validation failures.

Implementation Instructions:

1. Create `app/DataTransferObjects/ValidatedAddress.php` with fields: `formattedAddress`, `streetAddress`, `locality`, `postalCode`, `country`, `latitude`, `longitude`, `provider`, `providerPlaceId`, and `rawPayload`.
2. Expose mapping provider settings through `config/maps.php`. `GOOGLE_MAPS_API_KEY` controls provider selection; free-provider endpoint/key settings are used only when the Google key is absent.
3. Create `app/Services/AddressValidationService.php` that accepts a free-text address query and locale, delegates to the selected mapping provider, calls exactly one active provider, and returns `ValidatedAddress` only when the provider returns one Belgian street-level or venue-level result with coordinates.
4. Implement a Google provider using the existing Google geocoding base URL and API key; it must reject non-BE results and results without coordinates or usable formatted address.
5. Implement a free-provider address validation adapter using the configured free-service geocoding endpoint and optional API key; it must parse the configured response format, reject non-BE results, and return the same `ValidatedAddress` shape. This provider is used only when Google is absent.
6. Extend or replace `geocoding_cache` storage so successful and failed address validations are cached by normalized query, locale, country, provider, and purpose `address_validation`; do not break existing city/postal-code search cache reads.
7. Keep `PostalCodeCoordinateService` for postal-code reference lookups until later stories replace session write paths.
8. Add structured logging for provider failures without logging API keys or full raw payloads.

Definition of Done:

- Address validation works with Google-selected and free-provider-selected configurations using mocked HTTP responses.
- Non-Belgian, ambiguous, coordinate-less, and not-found provider responses return validation failures instead of silently storing postal-code centers.
- Cached successful and failed validations avoid duplicate HTTP calls.
- Unit tests cover Google success/failure, free-provider success/failure, cache hits, provider selection, no cross-provider fallback, and secret-safe logging.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.

## Story 2

Title: Store Normalized Address Data on Address-Bearing Models

Context: `sport_sessions` has `location`, `postal_code`, `latitude`, and `longitude`; `coach_profiles` has `postal_code` and `country`. Production has no columns for formatted address, street address, locality, provider, provider place ID, geocoding timestamp, or provider payload. Admin exports currently include postal code but not exact address provenance.

Problem: Even if the UI validates an address, the database cannot persist enough normalized address information to prove what was selected, show a complete address, backfill safely, or distinguish exact street coordinates from postal-code center coordinates.

Solution: Add explicit normalized address columns to every address-bearing model while preserving legacy columns for compatibility during migration.

Implementation Instructions:

1. Create a migration for `sport_sessions` adding nullable columns: `formatted_address`, `street_address`, `locality`, `country` default `BE`, `geocoding_provider`, `geocoding_place_id`, `geocoded_at`, and `geocoding_payload` JSON.
2. Create a migration for `coach_profiles` adding nullable columns: `formatted_address`, `street_address`, `locality`, `latitude`, `longitude`, `geocoding_provider`, `geocoding_place_id`, `geocoded_at`, and `geocoding_payload` JSON.
3. Keep existing `location`, `postal_code`, and `country` columns for backward compatibility; do not drop or rename them in this story.
4. Update `SportSession` and `CoachProfile` fillable/casts for the new fields, using `datetime` for `geocoded_at`, `array` for `geocoding_payload`, and decimal casts for coach profile coordinates.
5. Update `database/factories/SportSessionFactory.php` and `database/factories/CoachProfileFactory.php` with states for validated precise addresses.
6. Update `DatabaseExportService` coach and session exports to include formatted address, locality, latitude, longitude, geocoding provider, and geocoded-at timestamp.

Definition of Done:

- Fresh SQLite migrations and MySQL-compatible migrations create the new columns reversibly.
- Existing code paths still work when new address columns are null.
- Model tests cover fillable fields and casts for both models.
- Export tests cover the new address and coordinate columns.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.

## Story 3

Title: Replace Session Address Entry With One Validated Address Field

Context: `SessionForm` currently exposes `location` and `postalCode`; `resources/views/livewire/session/create.blade.php` and `resources/views/livewire/session/edit.blade.php` render two separate fields. `SessionService` resolves session coordinates from the postal code through `PostalCodeCoordinateService`, including recurring sessions and group updates.

Problem: Coaches can create or update a session with a venue name plus postal code that produces only postal-code center coordinates. The session detail map and directions can therefore point to the wrong place.

Solution: Session create/edit must use a single address field, validate it through `AddressValidationService`, and persist the normalized address and exact coordinates from the selected provider result.

Implementation Instructions:

1. Update `SessionForm` to expose one user-facing `addressQuery` field plus hidden normalized address fields required by `ValidatedAddress`; remove user-facing postal-code validation from the form.
2. Update `create.blade.php` and `edit.blade.php` to render one localized address input with an explicit validate/select action and a confirmation state showing the selected formatted address.
3. In `Create` and `Edit` Livewire components, call `AddressValidationService` before saving; block save with localized validation errors when the address is missing, unvalidated, stale after editing, outside Belgium, or missing coordinates.
4. Update `SessionService` create/update/createRecurring/updateGroup to accept normalized address data and set `location`, `postal_code`, `formatted_address`, `street_address`, `locality`, `country`, `latitude`, `longitude`, `geocoding_provider`, `geocoding_place_id`, `geocoded_at`, and `geocoding_payload` from `ValidatedAddress`.
5. Remove `PostalCodeCoordinateService` from `SessionService` constructor after all session write paths use validated address data.
6. Update `publish()` validation so a publishable session requires a validated formatted address and exact latitude/longitude, not only `location` and `postal_code`.
7. Ensure recurring session creation resolves the address once and applies the same normalized address data to every generated session.
8. Update session tests that currently set only `postal_code` so they use the validated-address factory state or provide exact coordinates and normalized address fields.

Definition of Done:

- A coach can create, edit, and bulk-update recurring sessions using one address field.
- Saved sessions contain formatted address, postal code, locality, provider metadata, and exact coordinates from the selected provider result.
- A session cannot be published when its address has not been validated or has no exact coordinates.
- Existing detail pages remain readable for legacy rows with only `location` and `postal_code`.
- Feature/Livewire tests cover create, edit, recurring create, recurring future update, invalid address, and stale-address validation.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.

## Story 4

Title: Replace Coach Profile Address Entry With One Validated Address Field

Context: Coach application step 2 and coach profile edit currently collect only `postal_code` and `country`. `coach_profiles` does not store coordinates, so coach geography cannot support precise address validation or future proximity features.

Problem: Coach address data remains postal-code-only even after session addresses become precise, violating the MVP requirement that all address fields use one validated address field and store exact coordinates.

Solution: Coach application and profile edit must use the same single validated address workflow and persist normalized address data and exact coordinates on `coach_profiles`.

Implementation Instructions:

1. Update `CoachApplicationForm` and `CoachProfileForm` to expose one user-facing `addressQuery` field plus hidden normalized address fields from `ValidatedAddress`; keep `postal_code` and `country` as persisted compatibility fields derived from the validated result.
2. Update `Application` step 2 and `ProfileEdit` save handling to validate the address through `AddressValidationService` and reject missing, stale, non-Belgian, or coordinate-less results.
3. Update `CoachApplicationService` to persist normalized address fields and coordinates when creating a pending profile.
4. Update `profile-edit.blade.php` and `application.blade.php` to show a single localized address input and selected-address confirmation instead of separate postal code/country inputs.
5. Update coach profile display and admin KYC review views, where applicable, to show the formatted address while preserving postal code visibility for legacy rows.
6. Update tests in `tests/Feature/Livewire/Coach` and notification tests that currently set only `form.postal_code`.

Definition of Done:

- Coach application and coach profile edit use one validated address field.
- Saved coach profiles contain formatted address, postal code, locality, country, provider metadata, and exact coordinates.
- Invalid, stale, non-Belgian, and coordinate-less addresses block submission with localized errors.
- Admin/coach views display formatted address for precise rows and keep legacy postal-code fallback readable.
- Feature/Livewire tests cover application submit, profile edit, validation failures, and KYC/read-only display where applicable.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.

## Story 5

Title: Use Precise Coordinates for Discovery Search, Maps, and Directions

Context: `Session\Index` already supports browser geolocation, but typed `locationQuery` uses `PostalCodeCoordinateService::resolveByLocationQuery`, so typed searches are city/postal-code center based. `Show` directions use lat/lng when present, but existing lat/lng values may be postal-code centers. Map markers use the session coordinates as stored.

Problem: Athletes cannot search by their current position plus precise session pins when session coordinates are postal-code centers, and typed address searches cannot use exact address coordinates. Directions can be technically coordinate-based while still leading to a postal-code center.

Solution: Discovery must use exact coordinates from validated session addresses and must resolve typed location searches through the address validation/geocoding layer before applying radius filters. Directions must always prefer validated exact coordinates and clearly fall back only for legacy rows.

Implementation Instructions:

1. Update `Session\Index` to use `AddressValidationService` for non-empty `locationQuery` values that are not resolved by the local postal-code/municipality lookup, caching the result and using its exact coordinates as the search center.
2. Keep browser geolocation as the highest-priority search center when `useGeolocation` is true.
3. Keep backward-compatible support for `postalCode` URL aliases during migration.
4. Update result cards, map popups, and session detail location display to use `formatted_address` when present and legacy `location` plus `postal_code` when not.
5. Update the directions URL generation so validated rows use `latitude,longitude`; legacy rows without validated exact coordinates use encoded formatted/legacy address text only as a fallback.
6. Add a small model helper on `SportSession`, such as `hasValidatedAddress()`, so views do not duplicate provider/coordinate checks.
7. Update translations for location search labels/placeholders so users can search by current position, full address, city, or postal code.

Definition of Done:

- Athlete discovery can use browser position, full address query, city/municipality, or postal code as the search center.
- Radius filtering and map markers remain consistent with the active search center.
- Session detail maps and directions use exact validated coordinates when available.
- Legacy rows remain viewable and searchable through existing postal-code/city behavior until backfilled.
- Tests cover full-address typed search with mocked provider result, browser geolocation priority, legacy postal-code alias, map marker consistency, and directions URL behavior for validated and legacy rows.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.

## Story 6

Title: Backfill and Monitor Production Address Precision

Context: OVH production currently has the schema needed for postal-code coordinates and geocoding cache, a configured Google Maps key, 9 sessions, 8 session rows with coordinates, 1 session missing coordinates, and an empty geocoding cache. Existing coordinates were produced by postal-code lookup and cannot be assumed precise.

Problem: Deploying precise address support will leave existing production rows in a mixed state unless operators can identify postal-code-center rows, validate exact addresses, and monitor readiness without creating demo data or exposing secrets.

Solution: Add production-safe commands and readiness checks that report address precision coverage, backfill exact addresses only from validated provider responses, and keep legacy rows visible until an operator validates them.

Implementation Instructions:

1. Create `php artisan addresses:audit-precision` to report counts for sessions and coach profiles: total rows, validated exact addresses, legacy postal-code-only rows, rows missing coordinates, rows missing formatted address, and provider distribution.
2. Create `php artisan addresses:backfill --model=sessions|coach_profiles --dry-run` that attempts provider validation only when enough legacy text exists to form a plausible Belgian address; dry-run must be the default behavior.
3. Require `--apply` to write normalized address fields and exact coordinates; never overwrite rows that already have `formatted_address`, provider metadata, and coordinates unless `--force` is explicitly passed.
4. Record audit events or structured logs for every applied row, including model type, model id, provider, and whether coordinates changed, without logging API keys.
5. Update `app/Livewire/Admin/Readiness.php` and `app/Console/Commands/MvpHealthSnapshot.php` to report address precision coverage separately from postal-code reference data.
6. Update `doc/Production-Readiness-Runbook.md` and `doc/MVP-Smoke-Test.md` with the new audit and backfill commands.
7. Add tests for dry-run behavior, apply behavior, no-overwrite behavior, missing-data skips, and readiness/status output.

Definition of Done:

- Operators can see exact-address coverage for sessions and coach profiles without inspecting the database manually.
- Backfill defaults to dry-run and writes only when `--apply` is passed.
- Existing postal-code-center coordinates are not silently treated as validated exact addresses.
- Readiness distinguishes postal-code reference availability from precise address validation coverage.
- Production runbook and MVP smoke test explain the deployment/backfill order.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.