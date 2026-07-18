# Phase 3 — Routing and Controllers

## Goal

Replace Phase 2 preview routes with controller-based Laravel routing and connect the existing Blade forms to a temporary validated request flow. This phase does not implement real authentication, persistence, models, migrations, middleware, email, SMS, or domain/business logic.

## Files created

- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/VerificationController.php`
- `app/Http/Controllers/DashboardController.php`
- `docs/PHASE_03_ROUTING_AND_CONTROLLERS.md`

## Files modified

- `routes/web.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/verify.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `tests/Feature/ExampleTest.php`

## Controller responsibilities

- `HomeController`: invokable controller that displays `auth.register`.
- `RegisterController::create`: displays the registration form.
- `RegisterController::store`: validates temporary registration input, flashes only the validated values for the preview request, and redirects to verification. It creates no user.
- `VerificationController::create`: displays the verification form.
- `VerificationController::store`: validates a temporary six-digit code and redirects to the dashboard. It does not verify a real code.
- `DashboardController`: invokable controller that displays `dashboard.index`; it has no authentication middleware in this phase.

## Routes

| Method | URI | Route name | Controller action |
|---|---|---|---|
| GET | `/` | `home` | `HomeController` |
| GET | `/register` | `register.create` | `RegisterController@create` |
| POST | `/register` | `register.store` | `RegisterController@store` |
| GET | `/verify` | `verification.create` | `VerificationController@create` |
| POST | `/verify` | `verification.store` | `VerificationController@store` |
| GET | `/dashboard` | `dashboard` | `DashboardController` |

There are no route closures and no `Route::view` preview routes remaining.

## Temporary request flow

```text
GET / or GET /register
        ↓
registration Blade form
        ↓ POST /register
temporary validation
        ↓ redirect
GET /verify
        ↓ POST /verify
temporary six-digit validation
        ↓ redirect
GET /dashboard
```

## Validation rules

Registration:

- `gmail`: `required`, `email`, `ends_with:@gmail.com`
- `phone`: `required`, `digits:11`, `starts_with:09`
- `username`: `required`, `string`, `min:3`, `max:50`

Verification:

- `code`: `required`, `digits:6`

The registration form uses `old()` and `@error` for each field. The six visual code inputs synchronize into one hidden, submitted `code` field. Invalid fields retain the Phase 2 design with an accessible visible error state.

## Redirects

- Valid registration input redirects to `verification.create`.
- Invalid registration input redirects back with validation errors and old input.
- A valid six-digit temporary code redirects to `dashboard`.
- An invalid code redirects back with a validation error.

## Tests

Feature tests cover:

- HTTP 200 for `/`, `/register`, `/verify`, and `/dashboard`
- Valid registration redirect
- Registration validation errors
- Valid verification redirect
- Verification validation errors

The tests assert only HTTP/session behavior and do not query a database.

## Intentionally not implemented

- User creation or database writes
- Eloquent model usage
- Authentication/login state
- Authorization or route middleware
- Real verification code generation or comparison
- Email or SMS delivery
- Resend and logout actions
- Session-backed identity or protected dashboard data

The registration values are flashed only as temporary preview data and are not persisted.

## Phase 4 readiness

The HTTP boundaries are now explicit and named. A later phase can replace temporary validation/redirect behavior with application logic while keeping the existing route names, form actions, views, and controller entry points stable.
