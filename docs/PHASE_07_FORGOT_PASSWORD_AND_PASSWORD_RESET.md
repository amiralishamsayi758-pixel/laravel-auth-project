# Phase 7: Forgot Password and Secure Password Reset

## Purpose

Phase 7 adds account-enumeration-resistant forgot-password requests and secure, expiring, single-use password resets using Laravel's built-in Password Broker, Password facade, notification, validation, hashing, and events.

## Laravel Password Broker and token lifecycle

The default `users` broker in `config/auth.php` uses the Eloquent user provider and the standard `password_reset_tokens` table created by the original Laravel migration. No duplicate migration or token system was added. `Password::sendResetLink()` creates the token and dispatches Laravel's built-in `ResetPassword` notification. Only a one-way hash is stored in the database; the raw token exists only in the reset URL. Tokens expire after 60 minutes and broker-level token generation is throttled for 60 seconds. A successful `Password::reset()` deletes the token, making it single-use.

## Gmail compatibility and notification routing

The project intentionally has `gmail`, not `email`. `User::getEmailForPasswordReset()` returns `gmail`, allowing the broker to identify token records consistently. `User::routeNotificationForMail()` sends mail notifications to the same Gmail address. `AppServiceProvider` configures Laravel's built-in reset notification URL as `password.reset` with both the token path parameter and Gmail query parameter.

## Controllers, routes, and middleware

`ForgotPasswordController` displays the request form, validates Gmail, invokes `Password::sendResetLink()`, and always returns the same Persian status for known, unknown, and broker-throttled accounts. This prevents account enumeration.

`ResetPasswordController` displays the route token and safely prefilled Gmail, validates the token/Gmail/confirmed strong password, and calls `Password::reset()`. Its callback assigns the plain new password once to the model's existing `hashed` cast, rotates `remember_token`, saves, and dispatches `PasswordReset`. It does not authenticate the user.

All routes use controllers and `guest` middleware:

- `GET /forgot-password` — `password.request`
- `POST /forgot-password` — `password.email`, with `throttle:6,1`
- `GET /reset-password/{token}` — `password.reset`
- `POST /reset-password` — `password.update`

The route throttle limits abusive submissions while broker throttling limits token regeneration per account.

## Forms and password security

The Persian forgot-password and reset-password views reuse the application layout and authentication styling. Forms include CSRF protection, accessible labels and errors, correct autocomplete values, and never repopulate password fields. The login page links to the forgot-password route and displays reset success status.

Registration and reset share `PasswordValidation`, requiring at least eight characters with letters, mixed case, numbers, and matching confirmation. The reset callback does not manually hash because the User model's `hashed` cast hashes the new value exactly once. Tests verify `Hash::check`, absence of the raw password in storage, remember-token rotation, event dispatch, guest state after reset, and rejection of invalid, expired, cross-user, and already-used tokens.

## Local mail behavior and testing

No production SMTP credentials are required. Local mail behavior follows the configured Laravel mailer. Tests use `Notification::fake()` and inspect the built-in notification, URL, and hashed token storage without sending real email. Feature tests also cover identical public responses for known and unknown Gmail addresses, route throttling, form rendering, validation, reset lifecycle, and login with new versus old credentials.

## Files created

- `app/Http/Controllers/Auth/ForgotPasswordController.php`
- `app/Http/Controllers/Auth/ResetPasswordController.php`
- `app/Support/PasswordValidation.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `tests/Feature/PasswordResetTest.php`
- `docs/PHASE_07_FORGOT_PASSWORD_AND_PASSWORD_RESET.md`

## Files modified

- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `resources/views/auth/login.blade.php`
- `routes/web.php`
- `tests/Unit/UserTest.php`

## Commands run

```text
php artisan migrate
php artisan route:list
php artisan test
vendor\bin\pint
```

`migrate:fresh` is intentionally not used.

## Known limitations

The following are explicitly not implemented:

- Production SMTP configuration
- Real Gmail delivery guarantee
- Queued email delivery
- Custom branded email templates
- Password change from dashboard
- Current-password confirmation
- Forced logout of every active device session
- SMS reset
- Two-factor authentication
