# Phase 6: Authentication, Login, and Logout

## Purpose

Phase 6 turns the verified registration flow into Laravel authentication. A password is collected securely, the verified user is logged in automatically, the dashboard is protected, and existing users can log in with either Gmail or username and log out through a CSRF-protected POST request.

## Files

Created:

- `app/Http/Controllers/Auth/LoginController.php`
- `database/migrations/2026_07_18_010000_make_users_password_required.php`
- `resources/views/auth/login.blade.php`
- `tests/Feature/AuthenticationTest.php`
- `docs/PHASE_06_AUTHENTICATION_LOGIN_LOGOUT.md`

Modified:

- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/VerificationController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Models/User.php`
- `bootstrap/app.php`
- `database/factories/UserFactory.php`
- `resources/views/auth/register.blade.php`
- `resources/views/dashboard/index.blade.php`
- `routes/web.php`
- `tests/Feature/RegistrationFlowTest.php`
- `tests/Unit/UserTest.php`

## Registration and password safety

Registration requires a confirmed password of at least eight characters containing letters, mixed case, and numbers. The raw password and confirmation are discarded after validation. Only `registration.password_hash`, produced by `Hash::make`, is retained temporarily in the session, and no user is created before code verification.

Verification requires complete temporary registration data and a valid temporary hash. The existing hash is assigned to the model; Laravel's `hashed` cast recognizes it as already hashed, preventing double hashing. Tests compare the temporary and stored hashes and use `Hash::check` against the original password. After creation, `Auth::login` authenticates the user, the session ID is regenerated, and all temporary registration state is removed.

## Login, middleware, dashboard, and logout

Login accepts one `login` value. Email-shaped input is matched against `gmail` and other input against `username`. `Auth::attempt` performs credential verification. Every credential failure returns the same Persian message so account existence is not disclosed. Successful login regenerates the session and uses an intended redirect to the dashboard.

Laravel's `guest` middleware protects home, registration, verification, and login routes and redirects authenticated users to the dashboard. The `auth` middleware protects the dashboard and logout and redirects guests to `/login`. The dashboard obtains its user from the authenticated request; the obsolete `registered_user_id` session mechanism has been removed from current runtime code and tests.

Logout exists only as a POST route. Its form includes a CSRF token. Logout calls `Auth::logout`, invalidates the session, regenerates the CSRF token, and redirects to login.

## Tests

Feature and unit tests cover registration password requirements and strength, confirmation, hash-only temporary storage, deferred user creation, verification, hash preservation, automatic login, temporary-session cleanup, Gmail and username login, generic credential errors, guest/auth redirects, authenticated dashboard data, POST logout, unavailable GET logout, fillable/hidden model attributes, casts, password hashing, and Laravel authentication compatibility.

## Commands used

```text
php artisan migrate
php artisan route:list
php artisan test
vendor\bin\pint
```

`migrate:fresh` is intentionally not used.

## Current limitations

The following are explicitly not implemented:

- Forgot password
- Password reset
- Real Gmail delivery
- Real verification links
- SMS verification
- Profile editing
- Account deletion
- Two-factor authentication

The verification code remains a development/testing code and must be replaced with a real delivery and expiring-challenge system in a later phase.
