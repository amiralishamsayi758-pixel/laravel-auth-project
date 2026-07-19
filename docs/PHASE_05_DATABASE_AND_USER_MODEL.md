# Phase 5 — Database, User Model, and Persistent Registration

## Purpose

Phase 5 replaces the temporary post-verification profile with a persisted `User`. Registration input remains temporary until the configured development code is accepted; only then is a user inserted and the dashboard begins reading from the database.

This is not an authentication system.

## Files created

- `database/migrations/2026_07_18_000000_update_users_table_for_persistent_registration.php`
- `config/verification.php`
- `app/Support/RegistrationValidation.php`
- `tests/Feature/RegistrationFlowTest.php`
- `tests/Unit/UserTest.php`
- `docs/PHASE_05_DATABASE_AND_USER_MODEL.md`

## Files modified

- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/VerificationController.php`
- `app/Http/Controllers/DashboardController.php`
- `resources/views/auth/register.blade.php`
- `resources/views/dashboard/index.blade.php`

## Files replaced

- Phase 4 `tests/Feature/ExampleTest.php` was replaced by `RegistrationFlowTest.php`.
- The placeholder `tests/Unit/ExampleTest.php` was replaced by the focused `UserTest.php`.

## Migration strategy

The default users migration had already been committed and had run in batch 1. It was therefore left unchanged. A new forward migration was created and run with `php artisan migrate`; `migrate:fresh` was neither needed nor used.

The new migration:

- renames `name` to `username`;
- renames `email` to `gmail`;
- renames `email_verified_at` to `gmail_verified_at`;
- adds unique `phone`;
- creates explicit unique constraints for `gmail` and `username`;
- makes the existing `password` nullable.

The password column was retained for compatibility with Laravel's default `Authenticatable` user shape, but made nullable because this flow has no password. No fake, default, hashed, or plain-text password is assigned. Password is not mass assignable.

## Users table schema

Application-relevant columns:

| Column | Definition |
|---|---|
| `id` | primary key |
| `gmail` | string, unique |
| `phone` | string, unique |
| `username` | string, unique |
| `gmail_verified_at` | nullable timestamp |
| `password` | nullable string, reserved for a future deliberate auth phase |
| `remember_token` | nullable remember-token string |
| `created_at`, `updated_at` | timestamps |

No verification-code column exists.

## User model

Mass-assignable attributes are limited to:

- `gmail`
- `phone`
- `username`
- `gmail_verified_at`

`gmail_verified_at` uses Laravel's `datetime` cast. Controller or request-flow logic is not placed in the model, and unguarded mass assignment is not used.

## Factory

`UserFactory` now generates:

- a unique lowercase Gmail address;
- a unique 11-digit Iranian-style phone beginning with `09`;
- a unique username containing only allowed characters;
- a verified timestamp.

It creates no password and no seed data is automatically inserted.

## Registration validation

Rules are centralized in `App\Support\RegistrationValidation` so register-time and verify-time checks remain identical.

- `gmail`: `required`, `email:rfc`, `lowercase`, `ends_with:@gmail.com`, `max:255`, `unique:users,gmail`
- `phone`: `required`, `digits:11`, `regex:/^09[0-9]{9}$/`, `unique:users,phone`
- `username`: `required`, `string`, `min:3`, `max:30`, `regex:/^[A-Za-z0-9_]+$/`, `unique:users,username`

Custom Persian messages include clear field-specific duplicate messages. Database constraints remain the final protection against races.

## Session lifecycle

Before verification:

- `registration.gmail`
- `registration.phone`
- `registration.username`

After successful persistence:

- `registered_user_id`

After persistence, the entire `registration` value and stale `verification.completed` flag are removed. Starting a new valid registration also clears stale `registered_user_id` and verification state.

## Verification flow

1. Verification GET/POST requires the temporary registration session.
2. The submitted code must contain exactly six digits.
3. In `local` and `testing` only, it is compared with `config('verification.development_code')` using `hash_equals`.
4. The current development-only code is `123456` and is defined once in `config/verification.php`.
5. Registration session values are revalidated, including uniqueness.
6. A database transaction creates the user, stores `registered_user_id`, and removes temporary session data.
7. The request redirects to the named dashboard route.

The code is not displayed in production-oriented UI and is not stored in the database. The fixed strategy must be replaced before production.

## Eloquent and database methods

- `User::create()` creates the verified user after code acceptance.
- `User::find()` resolves the dashboard user from `registered_user_id`.
- `DB::transaction()` groups user creation with the success-session transition.
- Test-only queries such as `User::query()->sole()` inspect isolated records.

Blade performs no database query.

## Duplicate and replay protection

- Register validation rejects existing Gmail, phone, or username values.
- Unique database indexes protect against concurrent races.
- The insertion catches `QueryException` and returns a safe Persian error without exposing database details.
- Registration is revalidated immediately before insertion.
- Successful verification removes `registration`, so replaying the POST cannot create another user.
- Dashboard rejects a missing or nonnumeric ID.
- Dashboard uses `User::find()` and clears a stale ID when its user was deleted.

## Dashboard data flow

```text
session: registered_user_id
           ↓
DashboardController
           ↓ User::find()
persisted User model
           ↓ explicit view data
dashboard/index.blade.php
```

The view displays Gmail, phone, username, verification status, and account creation time using escaped Blade output.

## Tests

Feature tests use `RefreshDatabase` and cover:

- registration page response;
- temporary registration session and no pre-verification user;
- wrong development code;
- verification without registration session;
- verified user creation and exact persisted values;
- verified timestamp;
- temporary session cleanup and `registered_user_id` storage;
- missing and stale dashboard IDs;
- persisted dashboard output;
- duplicate Gmail, phone, and username;
- repeated verification replay protection.

Focused unit tests verify User fillable attributes and the verification timestamp cast. Tests never depend on existing records.

## Commands executed

```text
php artisan about --only=drivers
php artisan migrate:status
php artisan migrate --no-interaction
php artisan test
vendor\bin\pint
php artisan route:list --except-vendor
php artisan view:cache
```

## Limitations

- Login is not implemented.
- Logout is not implemented.
- Auth middleware is not implemented.
- Password authentication is not implemented.
- Email delivery is not implemented.
- SMS delivery is not implemented.
- The fixed verification code is development-only.
- The session user ID is a prototype navigation mechanism, not authenticated identity.

## Phase 6 readiness

The schema, model, unique validation, transactional persistence, replay protection, and database-backed dashboard are ready for a separately designed authentication phase. Phase 6 must define password/identity strategy, code delivery and expiry, rate limiting, middleware, and security boundaries rather than treating this prototype session ID as authentication.
