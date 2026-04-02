---
description: "Scaffold a Stripe webhook handler for a given event type, with signature verification, event routing, service logic, and Pest tests"
agent: "agent"
argument-hint: "Stripe event type, e.g. checkout.session.completed"
tools: [search, createFile, editFile, runInTerminal]
---

# Scaffold a Stripe Webhook Handler

Generate all the files needed to handle the Stripe event type provided by the user.

## Project Context

- This is a **Laravel 12** project using **Stripe Connect** and **Laravel Cashier**.
- Follow the conventions in [copilot-instructions.md](../../.github/copilot-instructions.md) and [php.instructions.md](../../.github/instructions/php.instructions.md).
- All monetary amounts are stored as **integers in cents** (EUR).
- Business logic belongs in **Service** classes (`app/Services/`), not controllers.
- Side effects (notifications, invoice generation) use **Events + Listeners**.
- Tests use **Pest** with SQLite `:memory:`.

## What to Generate

For the given Stripe event type (e.g. `checkout.session.completed`), create:

### 1. Webhook Controller Method

Add a handler method to the Stripe webhook controller (create `app/Http/Controllers/StripeWebhookController.php` if it doesn't exist). The controller must:

- Extend `Laravel\Cashier\Http\Controllers\WebhookController` (which handles signature verification automatically).
- Define a `handle{EventName}` method following Cashier's convention (e.g. `handleCheckoutSessionCompleted` for `checkout.session.completed`).
- Delegate business logic to a Service class — the controller only parses the payload and dispatches.

### 2. Service Class

Create or update a service in `app/Services/` to handle the business logic for this event. The service must:

- Accept the Stripe event payload array as input.
- Use DB transactions where state changes are involved.
- Store monetary values in **cents**.
- Throw domain-specific exceptions on failure.

### 3. Event & Listener (if side effects apply)

If the webhook triggers side effects (emails, notifications, invoice generation, payout updates), create:

- An **Event** class in `app/Events/` carrying the relevant domain data.
- A **Listener** class in `app/Listeners/` that performs the side effect.
- Register the event-listener mapping (or use auto-discovery).

### 4. Pest Feature Test

Create a test in `tests/Feature/` that:

- Sends a fake Stripe webhook payload to the webhook route.
- Asserts the correct HTTP 200 response.
- Asserts the expected database state changes.
- Asserts that expected events were dispatched.
- Tests edge cases: duplicate delivery (idempotency), missing/malformed payload.

### 5. Route Registration

Ensure the webhook route is registered in `routes/web.php` or `routes/api.php`:

```php
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');
```

Cashier may already register this — check first and skip if so.

## Output Format

- Generate each file with its full path.
- Use `php artisan make:*` generators where appropriate, then edit the generated files.
- Add PHPDoc annotations to all public methods.
- Follow PSR-12 and the project's naming conventions.
- After generating, list all created/modified files.
