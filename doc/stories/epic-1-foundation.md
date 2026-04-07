# Epic 1: Foundation & Identity

> **Milestone**: Epic 1: Foundation & Identity
> **Depends on**: Issue #15 (prerequisites — OAuth credentials, GitHub labels/milestones)
> **Goal**: Deliver a working authentication system with four roles, MFA, OAuth, Sanctum tokens, master layout, i18n, and admin KYC portal.

---

## E1-S01 · UserRole enum and role migration

**Labels**: `auth`, `infrastructure`
**Size**: S
**Dependencies**: none

Add the `UserRole` backed enum and the `role` column to the `users` table.

### Acceptance Criteria

- [ ] `app/Enums/UserRole.php` exists as a backed string enum with values: `coach`, `athlete`, `accountant`, `admin`
- [ ] Migration adds `role` column (string, default `athlete`) to `users` table
- [ ] `User` model casts `role` to `UserRole`
- [ ] Unit test confirms enum values and casting

### Files to create/modify

- `app/Enums/UserRole.php`
- `database/migrations/xxxx_add_role_to_users_table.php`
- `app/Models/User.php`
- `tests/Unit/Enums/UserRoleTest.php`

---

## E1-S02 · EnsureUserHasRole middleware

**Labels**: `auth`
**Size**: S
**Dependencies**: E1-S01

Create the role-checking middleware and register it.

### Acceptance Criteria

- [ ] `app/Http/Middleware/EnsureUserHasRole.php` accepts one or more role slugs
- [ ] Aborts with 403 if user's role is not in the allowed list
- [ ] Registered as `role` alias in `bootstrap/app.php`
- [ ] Feature test: authenticated user with correct role passes; wrong role gets 403

### Files to create/modify

- `app/Http/Middleware/EnsureUserHasRole.php`
- `bootstrap/app.php`
- `tests/Feature/Middleware/EnsureUserHasRoleTest.php`

---

## E1-S03 · Install Laravel Fortify (backend auth)

**Labels**: `auth`, `infrastructure`
**Size**: S
**Dependencies**: E1-S01

Install and configure Fortify as the headless auth backend (login, register, password reset, email verification). No views — Livewire will provide the UI.

### Acceptance Criteria

- [ ] `laravel/fortify` installed via Composer
- [ ] `FortifyServiceProvider` registered
- [ ] `config/fortify.php` published and configured (features: registration, resetPasswords, emailVerification)
- [ ] Fortify views are disabled (we provide Livewire views)
- [ ] `User` model implements `MustVerifyEmail`
- [ ] Tests: registration creates user with `athlete` role; login works; password reset flow works

### Files to create/modify

- `composer.json` (require fortify)
- `config/fortify.php`
- `app/Providers/FortifyServiceProvider.php`
- `app/Models/User.php` (implements `MustVerifyEmail`)
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/Auth/LoginTest.php`
- `tests/Feature/Auth/PasswordResetTest.php`

---

## E1-S04 · Livewire auth views (Login, Register, Password Reset)

**Labels**: `auth`, `ui`
**Size**: M
**Dependencies**: E1-S03, E1-S08

Create Livewire components and Blade views for the authentication pages. Uses Form Objects per Livewire conventions.

### Acceptance Criteria

- [ ] `app/Livewire/Auth/Login.php` + `LoginForm.php` — email/password login
- [ ] `app/Livewire/Auth/Register.php` + `RegisterForm.php` — name, email, password, password confirmation
- [ ] `app/Livewire/Auth/ForgotPassword.php` — sends reset link
- [ ] `app/Livewire/Auth/ResetPassword.php` — reset with token
- [ ] `app/Livewire/Auth/VerifyEmail.php` — email verification notice page
- [ ] All views use the master layout (`layouts/app.blade.php`)
- [ ] All user-facing strings use `__()` (i18n-ready)
- [ ] Mobile-first responsive design (Tailwind)
- [ ] Livewire component tests for each page

### Files to create/modify

- `app/Livewire/Auth/Login.php`, `Register.php`, `ForgotPassword.php`, `ResetPassword.php`, `VerifyEmail.php`
- `app/Livewire/Forms/LoginForm.php`, `RegisterForm.php`
- `resources/views/livewire/auth/login.blade.php`, `register.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`, `verify-email.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Auth/LoginTest.php`, `RegisterTest.php`

---

## E1-S05 · Two-Factor Authentication — TOTP (Authenticator App)

**Labels**: `auth`
**Size**: M
**Dependencies**: E1-S03

Enable Fortify's TOTP-based two-factor authentication. Authenticator apps (Google Authenticator, Authy) generate time-based codes.

### Acceptance Criteria

- [ ] Fortify `twoFactorAuthentication` feature enabled in `config/fortify.php`
- [ ] Migration for `two_factor_*` columns on users table (Fortify provides this)
- [ ] User can enable 2FA from profile settings: QR code displayed, recovery codes generated
- [ ] Login flow: after password, if 2FA is enabled, prompt for TOTP code
- [ ] Recovery codes work as fallback
- [ ] 2FA is **optional** for coach/athlete roles
- [ ] Feature test: login with 2FA enabled requires code; invalid code is rejected

### Files to create/modify

- `config/fortify.php` (enable feature)
- `app/Livewire/Auth/TwoFactorChallenge.php`
- `resources/views/livewire/auth/two-factor-challenge.blade.php`
- `tests/Feature/Auth/TwoFactorAuthenticationTest.php`

---

## E1-S06 · Two-Factor Authentication — Email Code

**Labels**: `auth`, `messaging`
**Size**: M
**Dependencies**: E1-S05

Add email-based 2FA as an alternative to TOTP. User receives a 6-digit code via email.

### Acceptance Criteria

- [ ] User can choose between TOTP and email-based 2FA in profile settings
- [ ] `two_factor_type` column added to users (enum: `totp`, `email`, `null`)
- [ ] When email 2FA is active, a 6-digit code is sent to the user's email on login
- [ ] Code expires after 10 minutes
- [ ] Rate-limited: max 5 attempts per code
- [ ] Notification class `TwoFactorCodeNotification` sends the email (localized)
- [ ] Feature test: email code flow end-to-end

### Files to create/modify

- `database/migrations/xxxx_add_two_factor_type_to_users_table.php`
- `app/Enums/TwoFactorMethod.php` (backed enum: `totp`, `email`)
- `app/Services/EmailTwoFactorService.php`
- `app/Notifications/TwoFactorCodeNotification.php`
- `lang/fr/notifications.php`, `lang/en/notifications.php`, `lang/nl/notifications.php` (2FA strings)
- `tests/Feature/Auth/EmailTwoFactorTest.php`

---

## E1-S07 · Enforce MFA for Admin and Accountant roles

**Labels**: `auth`
**Size**: S
**Dependencies**: E1-S05, E1-S06

Admin and accountant users **must** have 2FA enabled. Redirect them to the 2FA setup page if they try to access protected routes without it.

### Acceptance Criteria

- [ ] `EnsureTwoFactorEnabled` middleware created
- [ ] Applied to all routes requiring `role:admin` or `role:accountant`
- [ ] If 2FA is not enabled, redirect to 2FA setup page with flash message
- [ ] Feature test: admin without 2FA is redirected; admin with 2FA passes through

### Files to create/modify

- `app/Http/Middleware/EnsureTwoFactorEnabled.php`
- `bootstrap/app.php` (register alias)
- `tests/Feature/Middleware/EnsureTwoFactorEnabledTest.php`

---

## E1-S08 · Master Blade layout + navigation + footer

**Labels**: `ui`, `i18n`
**Size**: M
**Dependencies**: E1-S01

Create the master layout, top navigation, mobile menu, user dropdown, footer, and toast system.

### Acceptance Criteria

- [ ] `resources/views/layouts/app.blade.php` — master layout with `$slot`, `$title`, `$head`
- [ ] `resources/views/components/nav/main.blade.php` — top nav bar (logo, nav links, auth-aware)
- [ ] `resources/views/components/nav/mobile-menu.blade.php` — off-canvas mobile nav
- [ ] `resources/views/components/nav/user-menu.blade.php` — avatar dropdown (logged-in state)
- [ ] `resources/views/components/nav/locale-switcher.blade.php` — FR / EN / NL toggle
- [ ] `resources/views/components/footer.blade.php`
- [ ] `resources/views/components/toast.blade.php` — toast notification container
- [ ] `resources/views/components/seo/meta.blade.php` — basic OG/SEO meta tags
- [ ] All strings use `__()` with keys from `lang/{locale}/common.php`
- [ ] Mobile-first responsive (Tailwind)
- [ ] Welcome page updated to use new layout

### Files to create/modify

- All the view files listed above
- `lang/fr/common.php`, `lang/en/common.php`, `lang/nl/common.php`
- `resources/views/welcome.blade.php` (refactored to use layout)

---

## E1-S09 · Locale detection middleware + switcher

**Labels**: `i18n`
**Size**: S
**Dependencies**: E1-S08

Detect browser language and allow manual locale switching. Store preference in session (and user profile if authenticated).

### Acceptance Criteria

- [ ] `SetLocale` middleware reads locale from: 1) session, 2) authenticated user preference, 3) `Accept-Language` header, 4) fallback `fr`
- [ ] Only `fr`, `en`, `nl` are accepted; anything else falls back to `fr`
- [ ] `GET /locale/{locale}` route sets the session locale and redirects back
- [ ] `preferred_locale` column added to users table (nullable string)
- [ ] Middleware registered globally
- [ ] Feature test: middleware picks correct locale from header; switch route works

### Files to create/modify

- `app/Http/Middleware/SetLocale.php`
- `database/migrations/xxxx_add_preferred_locale_to_users_table.php`
- `routes/web.php` (locale switch route)
- `bootstrap/app.php` (register middleware)
- `tests/Feature/Middleware/SetLocaleTest.php`

---

## E1-S10 · Base translation files (fr / en / nl)

**Labels**: `i18n`
**Size**: S
**Dependencies**: none

Create the initial translation file structure with common keys used across the application.

### Acceptance Criteria

- [ ] `lang/fr/common.php` — shared labels (buttons, status, nav items, form labels)
- [ ] `lang/en/common.php` — English mirror
- [ ] `lang/nl/common.php` — Dutch mirror
- [ ] `lang/fr/auth.php`, `lang/en/auth.php`, `lang/nl/auth.php` — auth-specific strings (login, register, etc.)
- [ ] `lang/fr/validation.php`, `lang/en/validation.php`, `lang/nl/validation.php` — Laravel validation messages (translate the defaults)
- [ ] All three locales have identical keys (no missing translations)

### Files to create/modify

- `lang/fr/*.php`, `lang/en/*.php`, `lang/nl/*.php`

---

## E1-S11 · Google OAuth login (Socialite)

**Labels**: `auth`
**Size**: M
**Dependencies**: E1-S03, E1-S01
**Blocked by**: Google OAuth credentials (Issue #15)

### Acceptance Criteria

- [ ] `laravel/socialite` installed via Composer
- [ ] `config/services.php` updated with `google` driver config (env vars: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`)
- [ ] `.env.example` updated with Google env placeholders
- [ ] `GET /auth/google/redirect` — redirects to Google consent screen
- [ ] `GET /auth/google/callback` — handles OAuth callback
- [ ] If email matches existing user → log in
- [ ] If email is new → create user with `athlete` role, email verified
- [ ] OAuth tokens are **not** stored long-term
- [ ] Google login button on login and register pages
- [ ] Feature test: callback with mock Socialite user creates/logs in user

### Files to create/modify

- `composer.json` (require socialite)
- `config/services.php`
- `.env.example`
- `app/Http/Controllers/Auth/GoogleController.php` (or Livewire — thin controller is fine for OAuth redirect)
- `routes/web.php`
- `tests/Feature/Auth/GoogleOAuthTest.php`

---

## E1-S12 · Facebook OAuth login (Socialite)

**Labels**: `auth`
**Size**: S
**Dependencies**: E1-S11
**Blocked by**: Facebook OAuth credentials (Issue #15)

Same pattern as Google, applied to Facebook.

### Acceptance Criteria

- [ ] `config/services.php` updated with `facebook` driver config
- [ ] `.env.example` updated with Facebook env placeholders
- [ ] `GET /auth/facebook/redirect` + `GET /auth/facebook/callback`
- [ ] Same create-or-login logic as Google
- [ ] Facebook login button on login and register pages
- [ ] Feature test: callback with mock Socialite user

### Files to create/modify

- `config/services.php`
- `.env.example`
- `app/Http/Controllers/Auth/FacebookController.php`
- `routes/web.php`
- `tests/Feature/Auth/FacebookOAuthTest.php`

---

## E1-S13 · Install Laravel Sanctum + API token infrastructure

**Labels**: `auth`, `infrastructure`
**Size**: S
**Dependencies**: E1-S01

Install Sanctum for API token authentication. Tokens are scoped per role. This enables future mobile/PWA frontends.

### Acceptance Criteria

- [ ] `laravel/sanctum` installed (may already be bundled with Laravel 12 — verify)
- [ ] `config/sanctum.php` published and configured
- [ ] Sanctum migrations run (personal_access_tokens table)
- [ ] `User` model uses `HasApiTokens` trait
- [ ] API token creation endpoint deferred — just the infrastructure for now
- [ ] Guard `api` configured to use Sanctum in `config/auth.php`
- [ ] Feature test: API request with valid token authenticates; without token gets 401

### Files to create/modify

- `composer.json` (if not already present)
- `config/sanctum.php`
- `config/auth.php`
- `app/Models/User.php`
- `tests/Feature/Auth/SanctumTokenTest.php`

---

## E1-S14 · User profile page (view + edit + 2FA settings)

**Labels**: `auth`, `ui`
**Size**: M
**Dependencies**: E1-S04, E1-S05, E1-S06

Profile page where users can view/edit their name, email, locale preference, and manage 2FA settings.

### Acceptance Criteria

- [ ] `app/Livewire/Profile/Edit.php` + `ProfileForm.php`
- [ ] Sections: personal info, password change, 2FA management, locale preference
- [ ] 2FA section: enable/disable TOTP, switch to email, view/regenerate recovery codes
- [ ] All strings localized
- [ ] Mobile-first layout
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Profile/Edit.php`
- `app/Livewire/Forms/ProfileForm.php`
- `resources/views/livewire/profile/edit.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Profile/EditTest.php`

---

## E1-S15 · UserPolicy + base authorization

**Labels**: `auth`
**Size**: S
**Dependencies**: E1-S01

Create the `UserPolicy` to guard user management actions.

### Acceptance Criteria

- [ ] `app/Policies/UserPolicy.php` created
- [ ] `viewAny`: admin only
- [ ] `view`: admin, or self
- [ ] `update`: admin, or self (own profile only)
- [ ] `delete`: admin only
- [ ] `promote`: admin only (change roles)
- [ ] `before()`: admin bypass
- [ ] Policy registered in `AuthServiceProvider` (or auto-discovered)
- [ ] Feature test: all 4 roles tested against each policy method

### Files to create/modify

- `app/Policies/UserPolicy.php`
- `tests/Feature/Policies/UserPolicyTest.php`

---

## E1-S16 · Coach application form (3-step wizard)

**Labels**: `coach`, `ui`
**Size**: M
**Dependencies**: E1-S04, E1-S08

Livewire multi-step form for athletes to apply as coaches. Submitted applications go into `pending_coach` status for admin review.

### Acceptance Criteria

- [ ] `app/Livewire/Coach/Application.php` + `CoachApplicationForm.php`
- [ ] Step 1: Specialties, bio, experience level
- [ ] Step 2: Geographic zone (postal code, country), enterprise number (Belgian BCE/KBO)
- [ ] Step 3: Confirmation + terms acceptance
- [ ] On submit: creates coach profile record with status `pending`; dispatches `NewCoachApplication` event
- [ ] Only athletes can apply (policy check)
- [ ] All strings localized
- [ ] Livewire component test for each step + submission

### Files to create/modify

- `app/Livewire/Coach/Application.php`
- `app/Livewire/Forms/CoachApplicationForm.php`
- `resources/views/livewire/coach/application.blade.php`
- `app/Models/CoachProfile.php` (or similar — the coach-specific profile data)
- `database/migrations/xxxx_create_coach_profiles_table.php`
- `app/Events/NewCoachApplication.php`
- `routes/web.php`
- `tests/Feature/Livewire/Coach/ApplicationTest.php`

---

## E1-S17 · Admin coach approval portal

**Labels**: `admin`, `ui`
**Size**: M
**Dependencies**: E1-S16, E1-S02

Admin page to review, approve, or reject coach applications.

### Acceptance Criteria

- [ ] `app/Livewire/Admin/CoachApproval.php`
- [ ] Lists pending coach applications with details
- [ ] Admin can **approve** → user role changes from `athlete` to `coach`; dispatches `CoachApproved` event
- [ ] Admin can **reject** with reason → dispatches `CoachRejected` event
- [ ] Protected by `role:admin` middleware
- [ ] Approved coaches get a verification badge flag
- [ ] All strings localized
- [ ] Feature test: approve flow, reject flow, non-admin denied

### Files to create/modify

- `app/Livewire/Admin/CoachApproval.php`
- `resources/views/livewire/admin/coach-approval.blade.php`
- `app/Services/AdminService.php` (approve/reject logic)
- `app/Events/CoachApproved.php`, `app/Events/CoachRejected.php`
- `routes/web.php`
- `tests/Feature/Livewire/Admin/CoachApprovalTest.php`

---

## E1-S18 · Coach approval / rejection email notifications

**Labels**: `messaging`, `coach`
**Size**: S
**Dependencies**: E1-S17

Send localized email notifications when a coach application is approved or rejected.

### Acceptance Criteria

- [ ] `app/Notifications/CoachApprovedNotification.php` — congratulations email
- [ ] `app/Notifications/CoachRejectedNotification.php` — rejection with reason
- [ ] `app/Listeners/SendCoachApprovedNotification.php` — listens to `CoachApproved`
- [ ] `app/Listeners/SendCoachRejectedNotification.php` — listens to `CoachRejected`
- [ ] Listeners registered in `EventServiceProvider`
- [ ] Notification content in `lang/{locale}/notifications.php`
- [ ] Feature test: approving coach triggers correct notification

### Files to create/modify

- `app/Notifications/CoachApprovedNotification.php`
- `app/Notifications/CoachRejectedNotification.php`
- `app/Listeners/SendCoachApprovedNotification.php`
- `app/Listeners/SendCoachRejectedNotification.php`
- `app/Providers/EventServiceProvider.php`
- `lang/fr/notifications.php`, `lang/en/notifications.php`, `lang/nl/notifications.php`
- `tests/Feature/Notifications/CoachApprovalNotificationTest.php`

---

## E1-S19 · New coach application notification to admin

**Labels**: `messaging`, `admin`
**Size**: XS
**Dependencies**: E1-S16

Notify admin users when a new coach application is submitted.

### Acceptance Criteria

- [ ] `app/Notifications/NewCoachApplicationNotification.php` — email to all admin users
- [ ] `app/Listeners/NotifyAdminsOfNewApplication.php` — listens to `NewCoachApplication`
- [ ] Notification content localized
- [ ] Feature test: submitting application triggers admin notification

### Files to create/modify

- `app/Notifications/NewCoachApplicationNotification.php`
- `app/Listeners/NotifyAdminsOfNewApplication.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Notifications/NewCoachApplicationNotificationTest.php`

---

## E1-S20 · GDPR / Privacy page (FR/EN/NL)

**Labels**: `ui`, `i18n`
**Size**: S
**Dependencies**: E1-S08, E1-S09

Static legal page translated in all three locales with print option.

### Acceptance Criteria

- [ ] `resources/views/pages/privacy.blade.php` — renders content based on current locale
- [ ] Content in `lang/fr/privacy.php`, `lang/en/privacy.php`, `lang/nl/privacy.php` (or dedicated Blade partials per locale)
- [ ] Print-friendly CSS (`@media print`)
- [ ] "Print" button on the page
- [ ] Route: `GET /privacy`
- [ ] Accessible from the footer
- [ ] Feature test: page renders in each locale

### Files to create/modify

- `resources/views/pages/privacy.blade.php`
- `lang/fr/privacy.php`, `lang/en/privacy.php`, `lang/nl/privacy.php`
- `routes/web.php`
- `tests/Feature/Pages/PrivacyPageTest.php`

---

## E1-S21 · `<x-money>` Blade component

**Labels**: `ui`
**Size**: XS
**Dependencies**: none

Display monetary amounts formatted as `€ XX,XX` from integer cents.

### Acceptance Criteria

- [ ] `resources/views/components/money.blade.php` — formats cents to `€ XX,XX`
- [ ] Uses Belgian formatting: comma as decimal separator, dot as thousands separator
- [ ] Usage: `<x-money :cents="$amount" />`
- [ ] Blade component test

### Files to create/modify

- `resources/views/components/money.blade.php` (or `app/View/Components/Money.php` if logic-heavy)
- `tests/Feature/Components/MoneyTest.php`

---

## E1-S22 · UserFactory update for roles

**Labels**: `testing`
**Size**: XS
**Dependencies**: E1-S01

Update the `UserFactory` to support creating users with specific roles.

### Acceptance Criteria

- [ ] `UserFactory` has states: `->coach()`, `->athlete()`, `->accountant()`, `->admin()`
- [ ] Default factory creates an `athlete`
- [ ] `->coach()` sets role to `coach`
- [ ] Used in all subsequent tests
- [ ] Unit test verifies each state

### Files to create/modify

- `database/factories/UserFactory.php`
- `tests/Unit/Factories/UserFactoryTest.php`

---

## Dependency Graph

```
E1-S01 (UserRole enum)
├── E1-S02 (Role middleware)
├── E1-S03 (Fortify)
│   ├── E1-S04 (Auth views) ──→ depends on E1-S08
│   │   ├── E1-S14 (Profile page)
│   │   └── E1-S16 (Coach application) ──→ E1-S17 (Admin approval)
│   │                                        ├── E1-S18 (Approval emails)
│   │                                        └── E1-S19 (Admin notification)
│   ├── E1-S05 (TOTP 2FA) ──→ E1-S06 (Email 2FA) ──→ E1-S07 (Enforce MFA)
│   ├── E1-S11 (Google OAuth) ──→ E1-S12 (Facebook OAuth)
│   └── E1-S13 (Sanctum)
├── E1-S08 (Master layout)
│   ├── E1-S09 (Locale middleware)
│   └── E1-S20 (Privacy page)
├── E1-S15 (UserPolicy)
└── E1-S22 (UserFactory)

E1-S10 (Translations) — no dependencies, can start immediately
E1-S21 (<x-money>) — no dependencies, can start immediately
```

## Suggested Implementation Order

1. **E1-S01**, **E1-S10**, **E1-S21**, **E1-S22** — no dependencies, can run in parallel
2. **E1-S02**, **E1-S03**, **E1-S08**, **E1-S15** — depend only on E1-S01
3. **E1-S05**, **E1-S09**, **E1-S13** — second wave
4. **E1-S04**, **E1-S11** — need Fortify + layout
5. **E1-S06**, **E1-S12** — build on previous auth work
6. **E1-S07**, **E1-S14**, **E1-S16** — features that combine auth + UI
7. **E1-S17**, **E1-S20** — admin portal + legal
8. **E1-S18**, **E1-S19** — notifications (last, depend on events)
