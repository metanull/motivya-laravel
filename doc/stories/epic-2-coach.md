# Epic 2: Coach "Business-in-a-Box"

> **Milestone**: Epic 2: Coach Business-in-a-Box
> **Depends on**: Epic 1 (auth, roles, layout, i18n)
> **Goal**: Coaches can create profiles, build sessions (including recurring), manage their calendar, and view basic statistics.

---

## E2-S01 · Session-related enums (SessionStatus, SessionLevel, ActivityType)

**Labels**: `infrastructure`, `coach`
**Size**: S
**Dependencies**: E1-S01

Define the backed enums used throughout session management.

### Acceptance Criteria

- [ ] `app/Enums/SessionStatus.php` — backed string enum: `draft`, `published`, `confirmed`, `completed`, `cancelled`
- [ ] `app/Enums/SessionLevel.php` — backed string enum: `beginner`, `intermediate`, `advanced`
- [ ] `app/Enums/ActivityType.php` — backed string enum with initial activities (Yoga, Strength, Running, Cardio, Pilates, Outdoor, Boxing, Dance, Padel, Tennis — extensible by admin later)
- [ ] Each enum has a `label(): string` method returning the localized name via `__()`
- [ ] Unit tests for each enum (values, labels)

### Files to create/modify

- `app/Enums/SessionStatus.php`
- `app/Enums/SessionLevel.php`
- `app/Enums/ActivityType.php`
- `lang/fr/sessions.php`, `lang/en/sessions.php`, `lang/nl/sessions.php` (status + level + activity labels)
- `tests/Unit/Enums/SessionStatusTest.php`
- `tests/Unit/Enums/SessionLevelTest.php`

---

## E2-S02 · CoachProfile model + migration

**Labels**: `coach`, `infrastructure`
**Size**: S
**Dependencies**: E1-S01, E1-S16 (migration may already exist from application form — extend it)

Full coach profile model with all business fields.

### Acceptance Criteria

- [ ] `coach_profiles` table with columns: `id`, `user_id` (FK), `status` (pending/approved/rejected), `specialties` (JSON), `bio`, `experience_level`, `postal_code`, `country` (default `BE`), `enterprise_number`, `is_vat_subject` (boolean), `stripe_account_id` (nullable), `stripe_onboarding_complete` (boolean, default false), `verified_at` (nullable timestamp), `timestamps`
- [ ] `CoachProfile` model with: `belongsTo(User)`, casts for JSON/boolean/enum fields
- [ ] `User` model: `hasOne(CoachProfile)` relationship
- [ ] `CoachProfileFactory` with states: `->pending()`, `->approved()`, `->rejected()`, `->vatSubject()`, `->nonVatSubject()`
- [ ] Unit test for model relationships and casts

### Files to create/modify

- `app/Models/CoachProfile.php`
- `database/migrations/xxxx_create_coach_profiles_table.php` (or adapt from E1-S16)
- `database/factories/CoachProfileFactory.php`
- `app/Models/User.php` (add relationship)
- `tests/Unit/Models/CoachProfileTest.php`

---

## E2-S03 · Session model + migration

**Labels**: `coach`, `infrastructure`
**Size**: S
**Dependencies**: E2-S01, E2-S02

The core Session model used by coaches to create sports activities.

### Acceptance Criteria

- [ ] `sessions` table (name: `sport_sessions` to avoid collision with Laravel's sessions table) with columns per the session-booking instructions: `id`, `coach_id` (FK to users), `activity_type`, `level`, `title`, `description`, `location`, `postal_code`, `latitude` (decimal 10,7 nullable), `longitude` (decimal 10,7 nullable), `date`, `start_time`, `end_time`, `price_per_person` (integer, cents), `min_participants`, `max_participants`, `current_participants` (default 0), `status` (default `draft`), `cover_image_id` (FK nullable), `recurrence_group_id` (nullable UUID for linking recurring sessions), `timestamps`
- [ ] `SportSession` model (to avoid `Session` namespace conflict) with casts for enums, relationships: `belongsTo(User, 'coach_id')`
- [ ] Indexes on: `coach_id`, `status`, `date`, `postal_code`, `(latitude, longitude)`
- [ ] `SportSessionFactory`
- [ ] Unit test for model, casts, relationships

### Files to create/modify

- `app/Models/SportSession.php`
- `database/migrations/xxxx_create_sport_sessions_table.php`
- `database/factories/SportSessionFactory.php`
- `tests/Unit/Models/SportSessionTest.php`

---

## E2-S04 · SessionPolicy

**Labels**: `auth`, `coach`
**Size**: S
**Dependencies**: E2-S03, E1-S15

Authorization rules for session CRUD operations.

### Acceptance Criteria

- [ ] `app/Policies/SessionPolicy.php`
- [ ] `viewAny`: any authenticated user
- [ ] `view`: any authenticated user (published/confirmed) or coach (own drafts) or admin
- [ ] `create`: coach or admin
- [ ] `update`: own coach or admin; not allowed if session is `completed` or `cancelled`
- [ ] `delete`: own coach or admin; only in `draft` status
- [ ] `cancel`: own coach or admin; only in `published` or `confirmed` status
- [ ] `before()`: admin bypass
- [ ] Feature test: all 4 roles × key scenarios

### Files to create/modify

- `app/Policies/SessionPolicy.php`
- `tests/Feature/Policies/SessionPolicyTest.php`

---

## E2-S05 · Session creation (Livewire form)

**Labels**: `coach`, `ui`
**Size**: M
**Dependencies**: E2-S03, E2-S04, E1-S08

Coach can create a new session via a Livewire form.

### Acceptance Criteria

- [ ] `app/Livewire/Session/Create.php` + `SessionForm.php`
- [ ] Fields: activity type (dropdown), level (dropdown), title, description, location, postal code, date, start time, end time, price (input in euros — converted to cents on save), min participants, max participants, cover image (select from admin library)
- [ ] Validation: all rules per session-booking instructions (end > start, max >= min, price > 0, future date)
- [ ] On save: creates session in `draft` status via `SessionService`
- [ ] Only accessible to coaches (`role:coach` middleware)
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Session/Create.php`
- `app/Livewire/Forms/SessionForm.php`
- `app/Services/SessionService.php` (create method)
- `resources/views/livewire/session/create.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Session/CreateTest.php`

---

## E2-S06 · Session editing

**Labels**: `coach`, `ui`
**Size**: S
**Dependencies**: E2-S05

Coach can edit their own sessions (with state guards).

### Acceptance Criteria

- [ ] `app/Livewire/Session/Edit.php` — reuses `SessionForm.php`
- [ ] Mount pre-fills form from existing session
- [ ] Cannot edit `completed` or `cancelled` sessions (policy enforced + UI shows read-only)
- [ ] Editing `published` or `confirmed` session: some fields locked (date, time cannot change if bookings exist)
- [ ] Update via `SessionService::update()`
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Session/Edit.php`
- `resources/views/livewire/session/edit.blade.php`
- `app/Services/SessionService.php` (update method)
- `routes/web.php`
- `tests/Feature/Livewire/Session/EditTest.php`

---

## E2-S07 · Session deletion + cancellation

**Labels**: `coach`
**Size**: S
**Dependencies**: E2-S05

Coach can delete draft sessions or cancel published/confirmed ones.

### Acceptance Criteria

- [ ] Delete: only `draft` sessions; hard delete
- [ ] Cancel: `published` or `confirmed` sessions → transitions to `cancelled` status
- [ ] Cancelling a `confirmed` session dispatches `SessionCancelled` event (will trigger refunds in Epic 3)
- [ ] `SessionService::delete()` and `SessionService::cancel()`
- [ ] Policy-checked
- [ ] Feature test

### Files to create/modify

- `app/Services/SessionService.php` (delete, cancel methods)
- `app/Events/SessionCancelled.php`
- `tests/Feature/Session/SessionDeletionTest.php`
- `tests/Feature/Session/SessionCancellationTest.php`

---

## E2-S08 · Session publishing (draft → published)

**Labels**: `coach`
**Size**: S
**Dependencies**: E2-S05

Coach publishes a draft session to make it visible to athletes.

### Acceptance Criteria

- [ ] `SessionService::publish()` — transitions from `draft` to `published`
- [ ] Validates all required fields are filled before publishing
- [ ] Button on session edit/show page
- [ ] Feature test: valid session publishes; incomplete session fails

### Files to create/modify

- `app/Services/SessionService.php` (publish method)
- `tests/Feature/Session/SessionPublishTest.php`

---

## E2-S09 · Recurring weekly sessions

**Labels**: `coach`
**Size**: M
**Dependencies**: E2-S05

Coach can create a recurring session ("Every Wednesday at 19:00") that generates multiple individual sessions.

### Acceptance Criteria

- [ ] Option on session creation form: "Repeat weekly" checkbox + "Number of weeks" input (max 12)
- [ ] On save: `SessionService::createRecurring()` creates N individual sessions sharing a `recurrence_group_id` (UUID)
- [ ] Each instance is an independent session (own status, own bookings)
- [ ] Editing a recurring session: choice to edit "this session only" or "all future sessions in group"
- [ ] Feature test: creating recurring generates correct dates; editing one vs all

### Files to create/modify

- `app/Services/SessionService.php` (createRecurring method)
- `app/Livewire/Session/Create.php` (add recurring fields)
- `app/Livewire/Forms/SessionForm.php` (add recurring fields)
- `tests/Feature/Session/RecurringSessionTest.php`

---

## E2-S10 · Activity cover images (admin upload + coach selection)

**Labels**: `admin`, `coach`, `ui`
**Size**: M
**Dependencies**: E2-S03

Admin uploads activity-specific cover images. Coaches select from this library.

### Acceptance Criteria

- [ ] `activity_images` table: `id`, `activity_type` (enum), `path`, `alt_text`, `uploaded_by` (FK), `timestamps`
- [ ] `ActivityImage` model
- [ ] `app/Livewire/Admin/ActivityImages.php` — admin uploads images (file validation: jpg/png/webp, max 2MB)
- [ ] Images stored via Laravel filesystem (local in dev, S3 in prod)
- [ ] Session creation form: image picker showing images filtered by selected activity type
- [ ] Feature test: admin uploads image; coach sees it in picker

### Files to create/modify

- `app/Models/ActivityImage.php`
- `database/migrations/xxxx_create_activity_images_table.php`
- `database/factories/ActivityImageFactory.php`
- `app/Livewire/Admin/ActivityImages.php`
- `resources/views/livewire/admin/activity-images.blade.php`
- `tests/Feature/Livewire/Admin/ActivityImagesTest.php`

---

## E2-S11 · Coach dashboard — upcoming sessions

**Labels**: `coach`, `ui`
**Size**: M
**Dependencies**: E2-S05

Dashboard page showing the coach's sessions grouped by status.

### Acceptance Criteria

- [ ] `app/Livewire/Coach/Dashboard.php`
- [ ] Tabs or sections: Upcoming (published + confirmed), Pending Draft, Past (completed + cancelled)
- [ ] Each session card shows: title, date, time, participant count, fill rate bar, status badge, price
- [ ] Quick actions: publish, edit, cancel
- [ ] Protected by `role:coach` middleware
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Coach/Dashboard.php`
- `resources/views/livewire/coach/dashboard.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Coach/DashboardTest.php`

---

## E2-S12 · Coach dashboard — basic statistics

**Labels**: `coach`, `ui`
**Size**: S
**Dependencies**: E2-S11

Stats cards on the coach dashboard: total sessions, total bookings, fill rate, total revenue.

### Acceptance Criteria

- [ ] Stats section on the coach dashboard (same Livewire component or a child component)
- [ ] Metrics: total sessions (all time), sessions this month, total bookings, average fill rate (%), total revenue (using `<x-money>`)
- [ ] Data loaded from DB queries (can be basic Eloquent aggregates for now)
- [ ] Feature test: correct numbers displayed

### Files to create/modify

- `app/Livewire/Coach/Dashboard.php` (add stats section)
- `resources/views/livewire/coach/dashboard.blade.php` (stats cards)
- `tests/Feature/Livewire/Coach/DashboardStatsTest.php`

---

## E2-S13 · Coach profile view + edit

**Labels**: `coach`, `ui`
**Size**: M
**Dependencies**: E2-S02, E1-S08

Public-facing coach profile page and coach's own edit view.

### Acceptance Criteria

- [ ] `app/Livewire/Coach/Profile.php` — public view of coach (specialties, bio, verified badge, upcoming sessions)
- [ ] `app/Livewire/Coach/ProfileEdit.php` + `CoachProfileForm.php` — coach edits their own profile
- [ ] Editable fields: specialties, bio, experience level, postal code, enterprise number
- [ ] `is_vat_subject` is read-only for the coach (set by admin or derived from enterprise number in the future)
- [ ] Route: `GET /coaches/{user}` (public), `GET /coach/profile/edit` (own)
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Coach/Profile.php`
- `app/Livewire/Coach/ProfileEdit.php`
- `app/Livewire/Forms/CoachProfileForm.php`
- `resources/views/livewire/coach/profile.blade.php`
- `resources/views/livewire/coach/profile-edit.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Coach/ProfileTest.php`
- `tests/Feature/Livewire/Coach/ProfileEditTest.php`

---

## E2-S14 · Session detail page (public)

**Labels**: `coach`, `athlete`, `ui`
**Size**: S
**Dependencies**: E2-S03, E1-S08

Public-facing session detail page visible to anyone. Booking action handled in Epic 3.

### Acceptance Criteria

- [ ] `app/Livewire/Session/Show.php`
- [ ] Displays: title, activity, level, coach name (linked to profile), date, time, location, price, spots remaining, status, cover image
- [ ] WhatsApp share button (`https://wa.me/?text=...`) + copy-link button
- [ ] Only `published` and `confirmed` sessions visible to non-coach/non-admin users
- [ ] Route: `GET /sessions/{sportSession}`
- [ ] All strings localized
- [ ] Feature test: renders correctly; draft session returns 403 for athletes

### Files to create/modify

- `app/Livewire/Session/Show.php`
- `resources/views/livewire/session/show.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Session/ShowTest.php`

---

## Dependency Graph

```
E2-S01 (Enums) ──→ E2-S03 (Session model)
E2-S02 (CoachProfile) ──→ E2-S13 (Profile view/edit)

E2-S03 (Session model)
├── E2-S04 (SessionPolicy)
├── E2-S05 (Session creation)
│   ├── E2-S06 (Session editing)
│   ├── E2-S07 (Deletion + cancellation)
│   ├── E2-S08 (Publishing)
│   ├── E2-S09 (Recurring sessions)
│   └── E2-S11 (Coach dashboard)
│       └── E2-S12 (Dashboard stats)
├── E2-S10 (Cover images)
└── E2-S14 (Session detail page)
```

## Suggested Implementation Order

1. **E2-S01**, **E2-S02** — enums and coach profile model
2. **E2-S03**, **E2-S04** — session model + policy
3. **E2-S05** — session creation form (core feature)
4. **E2-S06**, **E2-S07**, **E2-S08** — CRUD completion
5. **E2-S09**, **E2-S10** — recurring + cover images
6. **E2-S11**, **E2-S12** — dashboard and stats
7. **E2-S13**, **E2-S14** — profile and public views
