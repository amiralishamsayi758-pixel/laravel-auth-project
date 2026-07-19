# Phase 4 — Validation, Session Flow, and Form Experience

## Goal

Improve the prototype request flow with Laravel validation, temporary session state, controller-level guards, accessible form feedback, and a complete six-digit code input experience. No authentication or persistence is implemented.

## Files modified

- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/VerificationController.php`
- `app/Http/Controllers/DashboardController.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/verify.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/js/app.js`
- `tests/Feature/ExampleTest.php`

## File created

- `docs/PHASE_04_VALIDATION_AND_SESSION.md`

## Session keys

- `registration.gmail`: validated temporary Gmail address
- `registration.phone`: validated temporary phone number
- `registration.username`: validated temporary username
- `verification.completed`: boolean prototype completion flag

No password or persistent user identity is stored. A new successful registration clears any prior `verification.completed` flag before replacing the temporary registration data.

## Validation rules

### Registration

- `gmail`: `required`, `email:rfc`, `lowercase`, `ends_with:@gmail.com`, `max:255`
- `phone`: `required`, `digits:11`, `regex:/^09[0-9]{9}$/`
- `username`: `required`, `string`, `min:3`, `max:30`, `regex:/^[A-Za-z0-9_]+$/`

Every registration rule has a custom Persian validation message.

### Verification

- `code`: `required`, `digits:6`

Verification errors also use custom Persian messages.

## Request flow

```text
GET / or /register
        ↓
POST /register
        ↓ validate
session: registration.*
        ↓
GET /verify
        ↓
POST /verify
        ↓ validate
session: verification.completed = true
        ↓
GET /dashboard
```

The session contains prototype state only and is not authentication.

## Controller guards

- Verification GET and POST redirect to `register.create` when `registration` is absent.
- Dashboard redirects to `register.create` when `verification.completed` is absent.
- Guards are explicit controller conditions; no middleware was created.

## Form experience

- Registration preserves values through `old()` and focuses the first invalid field.
- Inline errors retain accessible `aria-invalid`, `aria-describedby`, and visible error styles.
- Verification preserves the entered code and focuses its first box.
- Six visual code boxes synchronize into the submitted hidden `code` field.
- JavaScript supports digit filtering, forward movement, Backspace navigation, full-code paste, form-submit synchronization, keyboard use, and mobile sizing.
- Dashboard displays the three temporary registration values with graceful placeholders.

## Tests

Feature coverage includes:

- Required registration validation
- Invalid Gmail, phone, and username independently
- Verification GET/POST without registration session
- Dashboard without completed verification
- Successful registration session storage and redirect
- Successful verification flag and redirect
- Invalid verification code
- Dashboard rendering of temporary session values
- Expected GET responses when the required prototype session exists

Tests use only HTTP and session assertions; they do not use a database.

## Limitations

- Any six-digit code is accepted; there is no real issued code.
- Session flags do not represent an authenticated identity.
- No expiry, resend, logout, rate limiting, email, or SMS exists.
- No database persistence or account creation exists.
- Controller guards are intentionally temporary and are not a replacement for authentication middleware.

## Phase 5 readiness

Phase 5 can build on stable named routes, validated request shapes, session key conventions, and tested guard behavior. Real identity or persistence work should replace—not silently extend—the prototype flags, with explicit security requirements and new tests.
