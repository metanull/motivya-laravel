# MVP Mapping Provider Coherence

## Epic

Title: Make Mapping a Coherent Google-or-Free Provider System

Context: Motivya currently has map-provider decisions split across `config/maps.php`, `app/Services/AddressValidationService.php`, `app/Services/GeocodingService.php`, `resources/views/components/session-map.blade.php`, `resources/js/session-map.js`, `resources/views/livewire/session/show.blade.php`, `app/Livewire/Admin/Readiness.php`, and `app/Console/Commands/MvpHealthSnapshot.php`. The verified current state is mixed: `GOOGLE_MAPS_API_KEY` is used for Google geocoding and Google directions, `MAPS_GEOCODING_PROVIDER` independently selects address validation, `resources/js/session-map.js` hardcodes OpenFreeMap tiles, and readiness only checks Google key shape plus geocoding cache existence. The `/sessions` map can fail to render when reached from the home page Browse Sessions link, and marker clusters can remain stale after filters change.

Problem: The app does not enforce the project rule that one selected mapping provider stack must own every mapping capability in a runtime configuration. Google Maps Platform must own map display, address validation, geocoding, discovery coordinate resolution, directions, and provider health checks when `GOOGLE_MAPS_API_KEY` is configured. The free-service stack must own those same capabilities only when `GOOGLE_MAPS_API_KEY` is absent. The current implementation allows mixed providers, silent fallback, hidden degradation, and incomplete operational health reporting.

Solution: Replace the current split map implementation with a provider-neutral mapping subsystem. Provider selection is computed once from configuration: `GOOGLE_MAPS_API_KEY` present selects Google; absent selects the free-service stack. All map rendering, address validation, geocoding, discovery coordinate resolution, directions URL generation, and readiness probes use the selected provider through shared services. Provider failures fail visibly and are reported through logs and readiness checks. Session discovery map rendering, navigation, and marker synchronization are fixed through the same provider-neutral map surface.

Cross-Cutting Constraints:

1. `GOOGLE_MAPS_API_KEY` is the only provider-selection switch. `MAPS_GEOCODING_PROVIDER` must be removed from `.env.example`, `config/maps.php`, tests, and documentation.
2. The Google provider stack must use Google Maps Platform for every mapping capability.
3. The free provider stack must use MapLibre with the configured OpenFreeMap style URL for map display, the configured Nominatim-compatible endpoint for address validation and geocoding, and the configured OpenStreetMap directions URL for directions.
4. The selected provider must not fall back to another provider after an API failure.
5. Required selected-provider capability failures must block the operation and show an actionable error state. They must not silently omit maps, skip validation, use postal-code centers as exact addresses, or return partial provider results.
6. Blade, Livewire components, and JavaScript entry points must consume provider-neutral DTOs. Provider URLs, API keys, probes, and request logic belong in config and services.
7. All user-visible provider, map, validation, readiness, and navigation text must be localized in `lang/fr`, `lang/en`, and `lang/nl`.
8. Each child story must keep unrelated behavior, migrations, dependencies, and documentation unchanged unless explicitly listed in that story.

Child Stories:

1. Replace map provider selection with one resolver.
2. Implement complete Google mapping services.
3. Implement complete free mapping services.
4. Refactor address validation and discovery geocoding to the selected provider.
5. Render session maps through provider-neutral components.
6. Generate directions through the selected provider.
7. Add selected-provider health checks to readiness tools.
8. Fix session discovery navigation and map initialization.
9. Keep session map markers and clusters synchronized with filters.
10. Update mapping configuration, tests, and operator documentation.

Completion Criteria:

- Google-configured environments use Google Maps Platform for every mapping capability.
- Environments without `GOOGLE_MAPS_API_KEY` use the complete configured free-service stack for every mapping capability.
- `MAPS_GEOCODING_PROVIDER` is no longer read, documented, or used by tests.
- Selected-provider API failures are visible to users or operators where appropriate and are reported through logs/readiness checks.
- Session discovery maps render consistently from every navigation entry point.
- Map markers and clusters match the active filtered session scope.
- Relevant automated tests and lint checks pass without warnings or errors.

---

## Story 1: Replace Map Provider Selection With One Resolver

Title: Replace map provider selection with one resolver

Context: `config/maps.php` currently contains `google_api_key`, Google endpoint URLs, OpenFreeMap/Nominatim endpoint settings, and `geocoding_provider`. `AddressValidationService` reads `config('maps.geocoding_provider')`, while `resources/js/session-map.js` always uses OpenFreeMap tiles. This permits geocoding, display, and directions to choose different providers in the same runtime.

Problem: The application has no single source of truth for the active mapping provider. `MAPS_GEOCODING_PROVIDER` creates a per-feature provider switch and violates the project rule that provider selection must be derived from whether `GOOGLE_MAPS_API_KEY` is configured.

Solution: Add one map provider resolver that selects `google` when `GOOGLE_MAPS_API_KEY` is configured and `free` when it is absent. Remove `MAPS_GEOCODING_PROVIDER` from configuration and make every mapping capability consume the resolver.

Implementation Instructions:

1. Create `app/Enums/MapProvider.php` with string-backed cases `Google = 'google'` and `Free = 'free'`.
2. Create `app/Services/Maps/MapProviderResolver.php` with a public method returning `MapProvider::Google` when `config('maps.google.api_key')` is a non-empty string and `MapProvider::Free` otherwise.
3. Restructure `config/maps.php` into provider-neutral settings plus `google` and `free` subsections. Read `GOOGLE_MAPS_API_KEY` only inside the `google` subsection.
4. Remove `geocoding_provider` from `config/maps.php` and remove `MAPS_GEOCODING_PROVIDER` from `.env.example`.
5. Add a configuration validation method on `MapProviderResolver` that returns a provider-neutral result object containing provider, capability name, status, and message for each required capability.
6. Update service container bindings so all map-related services receive `MapProviderResolver` instead of reading provider env values directly.
7. Add unit tests in `tests/Unit/Services/Maps/MapProviderResolverTest.php` proving Google is selected when the key is present, free is selected when the key is absent, and `MAPS_GEOCODING_PROVIDER` has no effect.

Definition of Done:

- `MAPS_GEOCODING_PROVIDER` is removed from `.env.example`, `config/maps.php`, and provider-selection tests.
- One resolver determines the active map provider for every map capability.
- Provider selection depends only on `GOOGLE_MAPS_API_KEY` presence.
- Invalid selected-provider configuration is represented as failed capability checks, not as a different selected provider.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 2: Implement Complete Google Mapping Services

Title: Implement complete Google mapping services

Context: Current Google logic is embedded in `AddressValidationService`, `GeocodingService`, and the session detail Blade directions block. Google-enabled environments still render maps through hardcoded OpenFreeMap tiles in `resources/js/session-map.js`.

Problem: A runtime with `GOOGLE_MAPS_API_KEY` configured still uses the free renderer for map display and inlined Google URL logic for directions. This is mixed-provider behavior and keeps provider code scattered across services, Blade, and JavaScript.

Solution: Add a complete Google provider implementation behind shared map contracts. The Google provider owns map render configuration, address validation, geocoding, discovery coordinate resolution, directions URL generation, and health probes when selected.

Implementation Instructions:

1. Create provider contracts under `app/Contracts/Maps/` for map render configuration, address validation, geocoding, directions URL generation, and health probing.
2. Create Google implementations under `app/Services/Maps/Google/` for those contracts.
3. Move Google geocoding request and response parsing out of `AddressValidationService` and `GeocodingService` into `GoogleAddressValidationProvider` and `GoogleGeocodingProvider`.
4. Add Google map render configuration that emits the Google Maps JavaScript API URL, required libraries, API key, fallback center, marker data, cluster setting, locale, and map container options through a provider-neutral DTO.
5. Add Google directions URL generation that produces `https://www.google.com/maps/dir/?api=1&destination={lat},{lng}` for validated coordinates and an encoded destination text URL only for legacy rows without coordinates.
6. Create `app/Exceptions/Maps/MapProviderException.php` and make Google HTTP errors, invalid responses, quota errors, and missing required response fields throw that exception with provider and capability context. Do not call any free-provider class from Google provider code.
7. Add unit tests covering Google address validation success, outside-Belgium rejection, no-result rejection, HTTP failure, geocoding cache keys, directions URL generation, map render DTO output, and no free-provider call when Google fails.

Definition of Done:

- With `GOOGLE_MAPS_API_KEY` configured, session maps are configured for Google Maps JavaScript and no OpenFreeMap tile URL is emitted.
- Google address validation, geocoding, directions, and health probes live under the Google provider implementation.
- Google provider failures do not call the free provider.
- Tests cover Google success and failure behavior for every required mapping capability.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 3: Implement Complete Free Mapping Services

Title: Implement complete free mapping services

Context: The existing free-service behavior is partial. `resources/js/session-map.js` uses MapLibre/OpenFreeMap for display, `AddressValidationService` can call a Nominatim-compatible endpoint, and session directions still use Google Maps URLs.

Problem: Environments without `GOOGLE_MAPS_API_KEY` do not have a complete free-provider stack because directions and some geocoding behavior remain tied to Google or independent fallback logic.

Solution: Add a complete free provider implementation behind the same map contracts. The free provider owns map display, address validation, geocoding, discovery coordinate resolution, directions URL generation, and health probes when Google is absent.

Implementation Instructions:

1. Create free provider implementations under `app/Services/Maps/Free/` for the map contracts created in Story 2.
2. Configure the free provider in `config/maps.php` with `style_url`, `geocoding_base_url`, `geocoding_api_key`, `directions_base_url`, `timeout`, `cache_ttl`, `attribution`, and probe address settings.
3. Use MapLibre with the configured OpenFreeMap style URL only inside the free map render implementation.
4. Use the configured Nominatim-compatible endpoint only inside the free address validation and geocoding implementations.
5. Generate free-provider directions URLs through the configured OpenStreetMap directions/search base URL. Do not generate Google directions URLs when `GOOGLE_MAPS_API_KEY` is absent.
6. Reject free-provider address validation responses that are outside Belgium, missing coordinates, missing a display name, or missing street/venue-level evidence required by the existing `ValidatedAddress` contract.
7. Make free-provider HTTP errors, invalid responses, missing endpoint configuration, and missing required response fields throw `MapProviderException` with provider and capability context. Do not call any Google provider class from free provider code.
8. Add unit tests covering free address validation success, outside-Belgium rejection, no-result rejection, HTTP failure, geocoding cache keys, directions URL generation, map render DTO output, and no Google-provider call when the free provider fails.

Definition of Done:

- Without `GOOGLE_MAPS_API_KEY`, no Google map, geocoding, address validation, or directions endpoint is emitted or called.
- The free provider supports every required mapping capability.
- Free-provider failures do not call Google and do not return partial map behavior.
- Tests cover free-provider success and failure behavior for every required mapping capability.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 4: Refactor Address Validation and Discovery Geocoding to the Selected Provider

Title: Refactor address validation and discovery geocoding to the selected provider

Context: `AddressValidationService` currently chooses between Google and OpenFreeMap through `config('maps.geocoding_provider')`. `GeocodingService` first checks local postal-code data and then calls Google when a key exists. `App\Livewire\Session\Index` calls `AddressValidationService` and then silently falls back to `PostalCodeCoordinateService` when validation returns null.

Problem: Address validation and discovery coordinate resolution are inconsistent and can silently degrade from provider validation to postal-code or municipality centers after provider failure.

Solution: Make `AddressValidationService`, `GeocodingService`, and session discovery use the selected provider contracts. Local postal-code or municipality centers remain available only as explicit approximate discovery centers, never as fallback after a selected-provider API failure and never as validated addresses.

Implementation Instructions:

1. Update `AddressValidationService` to delegate to the selected provider's address validation contract and remove all provider-specific HTTP code from the service.
2. Update `GeocodingService` to delegate to the selected provider's geocoding contract and remove direct Google HTTP code from the service.
3. Add a provider-neutral `ResolvedLocation` DTO with `latitude`, `longitude`, `source`, `precision`, `query`, and `provider` fields. Valid `precision` values are `exact`, `approximate`, and `browser`.
4. Update `PostalCodeCoordinateService` usage so postal-code and municipality matches return `ResolvedLocation` with `precision = approximate` and `provider = null`.
5. Update `App\Livewire\Session\Index` so provider failures are not passed to `PostalCodeCoordinateService`. Only user input classified as postal code or municipality before provider calls may use local approximate coordinates.
6. Update the session create/edit address workflows so saving requires `ValidatedAddress` from the selected provider and never accepts `ResolvedLocation` with approximate precision.
7. Cache provider lookup results by provider, purpose, normalized query, locale, country, and precision. Cache keys must not collide between Google and free provider results.
8. Add tests covering exact address validation, approximate postal-code discovery, provider failure without local fallback, invalid user input, and cache separation by provider and purpose.

Definition of Done:

- Address validation calls only the selected provider stack.
- Discovery geocoding calls only the selected provider stack for exact address queries.
- Postal-code and municipality centers are labelled approximate and are never persisted as validated exact addresses.
- Selected-provider failures do not fall back to local postal-code centers.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 5: Render Session Maps Through Provider-Neutral Components

Title: Render session maps through provider-neutral components

Context: `resources/views/components/session-map.blade.php` directly calls `sessionMap(...)` and pushes `resources/js/session-map.js`. The JavaScript imports MapLibre and hardcodes the OpenFreeMap style URL. This cannot render Google maps when Google is selected.

Problem: Map rendering is coupled to one free renderer and cannot switch coherently with the selected provider.

Solution: Replace the current hardcoded map component with provider-neutral Blade data and provider-specific JavaScript modules selected from server-generated render configuration.

Implementation Instructions:

1. Create a provider-neutral `MapRenderConfig` DTO containing provider, container ID, center, zoom, markers, clustering flag, locale, script URLs, style URLs, attribution, and failure message key.
2. Add a `MapRenderConfigService` that returns `MapRenderConfig` from the selected provider's map render contract.
3. Update `resources/views/components/session-map.blade.php` to accept a `MapRenderConfig` object and render provider-neutral JSON script data in an `application/json` script tag keyed by map container ID.
4. Split `resources/js/session-map.js` into provider-neutral bootstrapping plus `resources/js/maps/google-session-map.js` and `resources/js/maps/free-session-map.js`.
5. Ensure the Google module loads Google Maps JavaScript only for `provider = google` and the free module loads MapLibre only for `provider = free`.
6. Remove the hardcoded OpenFreeMap style URL from JavaScript. The free style URL must come from `config/maps.php` through `MapRenderConfig`.
7. Make map initialization idempotent for Livewire navigation by destroying the existing map instance for the same container before creating a new one.
8. Add feature tests asserting the rendered component emits Google config when Google is selected and free config when Google is absent.
9. Add browser tests proving both provider modules render markers and clusters from the provided marker dataset.

Definition of Done:

- Session map Blade and bootstrapping JavaScript contain no hardcoded provider endpoint URLs.
- Google-selected environments emit only Google map render configuration.
- Free-selected environments emit only free map render configuration.
- Map initialization works after Livewire navigation and repeated component mounts.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 6: Generate Directions Through the Selected Provider

Title: Generate directions through the selected provider

Context: `resources/views/livewire/session/show.blade.php` builds directions URLs inline using `config('maps.google_directions_base_url')`. This calls Google directions even when the free provider should be active.

Problem: Directions are provider-specific and currently bypass the mapping provider selection rule.

Solution: Move directions URL generation into a provider-neutral service that delegates to the selected provider.

Implementation Instructions:

1. Create `app/Services/Maps/DirectionsUrlService.php` that accepts a `SportSession` and returns a provider-neutral DTO containing URL, provider, destination label, and precision.
2. Implement Google directions generation in the Google provider using Google Maps directions URLs.
3. Implement free directions generation in the free provider using the configured OpenStreetMap directions/search base URL.
4. Update `resources/views/livewire/session/show.blade.php` to consume `DirectionsUrlService` output and remove inline directions URL construction.
5. Use validated exact coordinates when `SportSession::hasValidatedAddress()` is true.
6. Use encoded address text for legacy rows without validated exact coordinates and mark the DTO precision as `legacy_text`.
7. Add localized UI text for exact-coordinate directions and legacy-address directions where the distinction is shown to users.
8. Add tests for Google exact coordinates, Google legacy text, free exact coordinates, free legacy text, and absence of cross-provider URLs.

Definition of Done:

- Direction links are generated only by `DirectionsUrlService` and selected-provider implementations.
- Google-selected environments emit Google directions URLs.
- Free-selected environments emit free-provider directions URLs and no Google URL.
- Session detail Blade contains no provider-specific directions URL construction.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 7: Add Selected-Provider Health Checks to Readiness Tools

Title: Add selected-provider health checks to readiness tools

Context: `app/Livewire/Admin/Readiness.php` currently has `checkGoogleMapsKey()` and `checkGeocodingCache()`. `app/Console/Commands/MvpHealthSnapshot.php` reports address precision but does not probe selected map-provider capabilities. Neither tool verifies map display config, address validation/geocoding probe health, directions config, or provider-specific failures.

Problem: Operators cannot verify that every required capability of the selected map provider is healthy before users hit broken mapping flows.

Solution: Add provider-capability readiness checks for the selected provider to the admin readiness page and health snapshot command.

Implementation Instructions:

1. Create `app/Services/Maps/MapProviderHealthService.php` that returns checks for selected provider, map display, address validation, geocoding, directions, cache, and recent provider failures.
2. For Google-selected environments, check Google API key presence and expected shape, Google Maps JavaScript render config, Google geocoding/address validation probe against a configured Belgian probe address, directions config, cache/table access, and recent provider failure records.
3. For free-selected environments, check free style URL config, Nominatim-compatible geocoding endpoint probe against the same Belgian probe address, free directions config, attribution config, cache/table access, and recent provider failure records.
4. Add provider failure logging persistence through a `map_provider_failures` table with provider, capability, message, occurred_at, and context columns. Store no API keys and no full provider payloads.
5. Replace `checkGoogleMapsKey()` and `checkGeocodingCache()` in `Readiness` with selected-provider capability checks from `MapProviderHealthService`.
6. Add the same capability checks to `MvpHealthSnapshot` and make red selected-provider failures produce exit code `1`.
7. Add localized readiness labels and remediation messages in `lang/fr/admin.php`, `lang/en/admin.php`, and `lang/nl/admin.php`.
8. Add tests for Google healthy, Google key missing selecting free, Google probe failure, free healthy, free endpoint missing, free probe failure, and health snapshot exit codes.

Definition of Done:

- Admin readiness shows the active map provider and separate statuses for map display, address validation, geocoding, directions, cache, and recent failures.
- `mvp:health-snapshot` includes the same selected-provider capability statuses.
- Failing selected-provider capabilities are red and actionable.
- No readiness check suggests switching provider after a selected-provider failure.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 8: Fix Session Discovery Navigation and Map Initialization

Title: Fix session discovery navigation and map initialization

Context: The home page Browse Sessions link in `resources/views/welcome.blade.php`, the top navigation links in `resources/views/components/nav/main.blade.php`, the mobile menu links in `resources/views/components/nav/mobile-menu.blade.php`, and the athlete contextual menu link in `resources/views/components/nav/user-menu.blade.php` all route to `sessions.index` in different navigation contexts. The map component currently relies on `@push('scripts')` inside `resources/views/components/session-map.blade.php`, which can behave differently across first load and Livewire `wire:navigate` transitions.

Problem: The session map can be missing when the user reaches `/sessions` from the home page Browse Sessions link, and navigation exposes redundant session discovery links.

Solution: Make every discovery entry point use one navigation pattern and make map assets initialize reliably for first page load and Livewire navigation.

Implementation Instructions:

1. Update `resources/views/welcome.blade.php`, `resources/views/components/nav/main.blade.php`, `resources/views/components/nav/mobile-menu.blade.php`, and `resources/views/components/nav/user-menu.blade.php` so every public/athlete discovery link points to `route('sessions.index')` with `wire:navigate`.
2. Remove the standalone top-bar Sessions link for authenticated athletes when the athlete contextual menu already contains `common.nav.athlete_sessions`.
3. Keep the coach top-bar session link pointing to `route('coach.sessions.create')` and label it with the coach create-session translation, not the athlete discovery translation.
4. Move provider-neutral map bootstrapping imports into `resources/js/app.js` so they are available before the session map component initializes on both normal page loads and Livewire navigation.
5. Add a browser event listener for Livewire navigation completion that initializes visible uninitialized map containers exactly once.
6. Add tests proving the home Browse Sessions link, desktop navigation, mobile navigation, and athlete contextual menu all reach `/sessions` and render a provider-specific map container.
7. Add a regression test proving the authenticated athlete top bar does not duplicate the contextual session discovery link.

Definition of Done:

- `/sessions` renders the map when reached from home Browse Sessions, desktop navigation, mobile navigation, and athlete contextual navigation.
- Athlete navigation has one contextual discovery link and no redundant standalone top-bar Sessions link.
- Coach navigation still exposes create-session behavior separately from athlete discovery.
- Map bootstrapping works on first load and after Livewire navigation.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 9: Keep Session Map Markers and Clusters Synchronized With Filters

Title: Keep session map markers and clusters synchronized with filters

Context: `App\Livewire\Session\Index` builds list results and map markers in the same render method, and `SessionQueryService::mapMarkers()` applies the current filters. The frontend map code initializes markers once on map load and only calls `setData()` when `addMarkers()` is called. The reported production behavior is that cluster counts can remain stale after date filters change.

Problem: Filter updates can refresh the visible session list without reliably replacing the frontend map source data, leaving stale cluster counts and markers.

Solution: Make marker data a first-class Livewire output that updates the provider-neutral map component after every filter, pagination, and location-state change.

Implementation Instructions:

1. Add a `SessionQueryService::filteredDiscoverableQuery()` method used by both paginated list results and marker collection generation.
2. Ensure markers include all sessions matching the active filters and location/radius scope, independent of the current pagination page.
3. Keep activity, level, date, time, status, publication, location center, and radius filters identical between list scope and marker scope.
4. Add a stable marker payload checksum in `App\Livewire\Session\Index` and pass it to the map component.
5. Update provider-neutral map bootstrapping so marker checksum changes trigger a provider-specific `replaceMarkers()` call that clears stale popups, replaces source data, and refreshes clusters.
6. Clear map markers when filters produce no matching sessions.
7. Add Livewire tests proving marker payloads change for date filters, activity filters, time filters, location/radius filters, and reset filters.
8. Add browser coverage proving cluster counts update after marker payload replacement.

Definition of Done:

- Map markers and clusters always reflect the active filters.
- Date filters reduce marker counts when fewer sessions match.
- Resetting filters restores the unfiltered marker set.
- No stale markers or popups remain after marker data changes.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.

## Story 10: Update Mapping Configuration, Tests, and Operator Documentation

Title: Update mapping configuration, tests, and operator documentation

Context: `.env.example`, `doc/stories/mvp-address-geolocation.md`, `doc/MVP-Stabilization-Milestone.md`, `doc/MVP-Smoke-Test.md`, `doc/Production-Readiness-Runbook.md`, and map-related tests still describe or exercise pieces of the old mixed-provider system.

Problem: Maintained documentation and tests can reintroduce mixed providers or silent fallback if they continue to mention independent geocoding provider switches, Google fallback geocoding, or MapLibre/OpenFreeMap display in Google-selected environments.

Solution: Update documentation, environment examples, and tests so they describe and enforce the hard Google-or-free provider system.

Implementation Instructions:

1. Update `.env.example` to remove `MAPS_GEOCODING_PROVIDER` and document the required `GOOGLE_MAPS_API_KEY`, Google provider settings, and free provider settings.
2. Update `doc/stories/mvp-address-geolocation.md` so address precision work delegates provider selection to the resolver introduced in this epic and does not describe an independent geocoding provider switch.
3. Update `doc/MVP-Stabilization-Milestone.md` so it no longer instructs implementers to keep MapLibre/OpenFreeMap display while using Google geocoding or directions.
4. Update `doc/MVP-Smoke-Test.md` with smoke steps for Google-selected and free-selected mapping readiness.
5. Update `doc/Production-Readiness-Runbook.md` with operator steps for verifying selected provider, map display, address validation/geocoding, directions, and recent provider failures.
6. Update existing map/address tests that refer to `MAPS_GEOCODING_PROVIDER` so they use `GOOGLE_MAPS_API_KEY` presence or absence to select the provider.
7. Add a documentation note that historical stored values in `geocoding_provider` are address provenance metadata only and do not control runtime provider selection.
8. Run markdown diagnostics, targeted map/address tests, and lint checks.

Definition of Done:

- Maintained docs no longer prescribe or test mixed map providers in one runtime.
- `.env.example` documents `GOOGLE_MAPS_API_KEY` as the provider-selection input and does not include `MAPS_GEOCODING_PROVIDER`.
- Operator docs explain selected-provider readiness for Google-selected and free-selected environments.
- Tests no longer use `MAPS_GEOCODING_PROVIDER` as a provider switch.
- The requested behavior is implemented and follows the applicable project conventions.
- Relevant automated tests are added or updated for the business logic and user-visible behavior.
- Lint checks pass without warnings or errors.
- Relevant test suites pass without warnings or errors.
- No unrelated behavior, migrations, dependencies, or documentation are changed unless this story explicitly requires them.
- Code is linted.
- All tests are passing.
- No warnings or errors in lint or tests.