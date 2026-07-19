# Phase 10: Authorization — Roles, Gates, Policies, and Admin Access

## Goals and boundary

Phase 10 adds a secure authorization foundation to the existing authentication system: a typed role, central admin Gate, model Policy, middleware-protected demonstration page, Blade authorization, and an explicit promotion command. A full user-management interface is intentionally deferred to Phase 11.

Authentication answers “who is this user?” Authorization answers “may this authenticated user perform this action?” Login, verification, password reset, and profile flows establish identity; Phase 10 decides access after identity is established.

## Roles and migration

`App\Enums\UserRole` is a string-backed enum with exactly:

- `User = 'user'`
- `Admin = 'admin'`

Migration `2026_07_19_010000_add_role_to_users_table` adds a portable, non-null string `role` with default `user`. Existing accounts safely receive the normal-user default; no account is automatically promoted. Rollback removes only this column.

`User` casts `role` to `UserRole` and provides `isAdmin()` and `isUser()`. The role is deliberately absent from `$fillable`, alongside the already-protected `gmail_verified_at` and `avatar_path`. Registration and profile mass assignment therefore cannot promote an account. Authorization changes require explicit trusted assignment such as the administrative command's `forceFill`.

## Gates versus Policies

A Gate represents a broad capability that is not tied to a particular target model. `access-admin` is defined once in `AppServiceProvider` and grants access only when `role === UserRole::Admin`. It works through `Gate`, `$user->can()`, `can` middleware, and Blade `@can`.

A Policy represents actions involving User records. Laravel conventionally discovers `App\Policies\UserPolicy` for `App\Models\User`.

| Ability | Normal user | Admin |
|---|---|---|
| `viewAny` | Denied | Allowed |
| `view` self | Allowed | Allowed |
| `view` another | Denied | Allowed |
| `update` self | Allowed | Allowed |
| `update` another | Denied | Allowed |
| `changeRole` another | Denied | Allowed |
| `changeRole` self | Denied | Denied |
| administrative `delete` another | Denied | Allowed |
| administrative `delete` self | Denied | Denied |

No broad policy `before()` hook is used. Such a hook could accidentally bypass explicit self-demotion and self-deletion restrictions. The existing self-service account deletion is separate: it requires the current password and does not use the future administrative delete ability.

## Admin route and middleware

`GET /admin`, named `admin.dashboard`, uses an invokable controller and this middleware chain:

```text
web -> auth -> verified -> can:access-admin
```

Guests redirect to login, unverified admins redirect to `verification.notice`, verified normal users receive 403, and verified admins receive 200. The minimal Persian admin page displays only the authenticated admin's safe username and navigation back to dashboard. It does not list or manage users. A matching custom `errors/403.blade.php` is included.

Dashboard navigation uses `@can('access-admin')`. Hiding a link is only a usability improvement, not a security boundary; direct requests remain protected by `can:access-admin` middleware.

In this project, unauthenticated access produces a redirect to login (rather than an exposed protected response), while an authenticated but unauthorized user receives HTTP 403.

## Admin promotion command

The command is:

```text
php artisan user:promote-admin user@example.com
```

Replace the example Gmail with an exact Gmail belonging to an existing account. The command never creates a user, verifies Gmail, changes profile data, or prints secrets. Missing accounts return a non-zero failure. Already-admin accounts return success without another change. Promotion explicitly saves `UserRole::Admin`, bypassing public mass assignment only inside this trusted command.

No admin password or production credential is seeded or committed. Initial administrative authority must be granted deliberately in the target environment.

## Privilege-escalation protections

- Role is enum-backed and not fillable.
- Default database and factory roles are normal user.
- Registration validation never retains submitted role in temporary session data.
- Verified registration creates a normal user.
- Profile, password, and avatar inputs cannot change role.
- Admin routing uses server-side Gate middleware, not navigation visibility.
- Policy self-demotion and administrative self-deletion are explicitly denied.
- No request accepts a role selector or target user ID in this phase.
- No role/permission package or scattered string role checks were introduced.

## Tests

Phase 10 tests cover enum cases, casts, defaults, helpers, fillable safety, mass-assignment resistance, conventional Policy discovery and its full ability matrix, Gate behavior, layered admin-route responses, Blade visibility, direct-access denial, registration/profile/password/avatar escalation attempts, command success/idempotency/failure/data preservation/unverified preservation, and sensitive-output safety. The entire earlier suite remains regression coverage.

## Manual verification checklist

1. Run migrations and register/verify a normal account.
2. Log in and confirm the dashboard has no admin link and `/admin` returns 403.
3. Log out and confirm `/admin` redirects to login.
4. Promote the existing account with its exact Gmail using the Artisan command.
5. Log in again and confirm the admin navigation and `/admin` page are available.
6. Create an unverified account, promote it, and confirm `/admin` still redirects to email verification.
7. Run the command again for an existing admin and confirm the idempotent message.
8. Run it with a missing Gmail and confirm a non-zero failure without user creation.

## Commands

```text
php artisan migrate
php artisan optimize:clear
php artisan route:list
php artisan test
vendor\bin\pint --test
php artisan user:promote-admin user@example.com
```

The final command is an administrative operation and must use an existing account's real Gmail. Phase 11 will build the authorized user-management UI on this foundation.
