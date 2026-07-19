# Phase 9: Profile Management

## Goals

Phase 9 adds verified-user profile editing, password changes, secure avatar lifecycle management, and permanent account deletion while preserving the manual authentication, custom Gmail verification, password reset, and verified-dashboard architecture from Phases 1–8.

## Routes

All Phase 9 routes use both `auth` and `verified` middleware.

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/profile` | `profile.edit` | `ProfileController@edit` |
| PATCH | `/profile` | `profile.update` | `ProfileController@update` |
| PUT | `/profile/password` | `profile.password.update` | `PasswordController@update` |
| POST | `/profile/avatar` | `profile.avatar.store` | `AvatarController@store` |
| DELETE | `/profile/avatar` | `profile.avatar.destroy` | `AvatarController@destroy` |
| DELETE | `/profile` | `profile.destroy` | `AccountDeletionController` |

No route accepts a user ID. Every operation derives ownership from `$request->user()`.

## Database and model

Migration `2026_07_19_000000_add_avatar_path_to_users_table` adds a reversible nullable string `avatar_path` to `users`. Existing authentication columns are unchanged. `avatar_path` is deliberately not added to `$fillable`; avatar controllers assign it explicitly with `forceFill`, so profile form payloads cannot overwrite it.

## Controller and Form Request responsibilities

- `ProfileController` renders the page and updates only validated `username`, `gmail`, and `phone`.
- `ProfileUpdateRequest` preserves existing formats and unique constraints while ignoring the authenticated user's own row.
- `PasswordController` changes the password through the existing hashed cast and rotates `remember_token`.
- `PasswordUpdateRequest` requires `current_password` and reuses the strong confirmed password policy from registration/reset.
- `AvatarController` stores, replaces, and removes only managed `avatars/` files through the public disk.
- `AvatarUpdateRequest` performs content-aware image validation, permits JPEG/PNG/WEBP, rejects SVG, and limits files to 2 MB.
- `AccountDeletionController` logs out, removes the managed avatar, deletes the account, invalidates the session, regenerates the CSRF token, and redirects publicly.
- `AccountDeletionRequest` validates the password with Laravel's `current_password` rule.

Each request has its own named error bag: `profileUpdate`, `passwordUpdate`, `avatarUpdate`, or `accountDeletion`. Errors therefore render only beside the relevant form.

## Profile and Gmail update flow

Only explicitly validated fields are filled; `request()->all()` is never used. An unchanged Gmail preserves `gmail_verified_at` and sends no notification. When Gmail changes, the controller saves the new Gmail, securely clears `gmail_verified_at`, sends exactly one standard verification notification, and redirects to `verification.notice`. The existing signed verification flow must be completed before the user can return to profile or dashboard.

## Password change security

Laravel's `current_password` rule verifies the authenticated user's current credential. The new password uses the shared minimum-eight-character mixed-case, letters, numbers, and confirmation policy. The plain password is assigned once to the User model's `hashed` cast, preventing manual double hashing. `remember_token` is rotated while the current authenticated session remains active.

## Avatar storage lifecycle

Avatars use Laravel's `public` filesystem disk and are stored beneath `avatars/` with framework-generated hashed filenames. Client filenames are never trusted or reused.

Replacement order is deliberately safe:

1. Validate file content and size.
2. Store the new file.
3. Persist the new `avatar_path` on the authenticated user.
4. If model persistence fails, remove the new file.
5. Only after persistence succeeds, remove the previous managed avatar.

Removal first clears the user's database reference and then deletes only paths beginning with `avatars/`. It is idempotent when no avatar exists and never accepts another user's path. The view renders the public URL with `Storage::disk('public')->url()` and shows a fallback initial when no avatar exists. SVG is rejected because it can contain executable/scriptable content; no image-processing dependency was added.

Run `php artisan storage:link` once in a local/deployed environment so public avatar URLs resolve through `public/storage`.

## Account deletion sequence

Account deletion requires the current password and performs this sequence:

1. Capture the authenticated user and managed avatar path.
2. Log out through Laravel Auth.
3. Remove the user's managed avatar from the public disk.
4. Permanently delete the user row.
5. Invalidate the session.
6. Regenerate the CSRF token.
7. Redirect to the public home route with a status message.

Soft deletes were not introduced. Wrong or missing passwords fail validation before any destructive action.

## Tests

`ProfileManagementTest` covers guest/unverified access, verified rendering, profile ownership, uniqueness, mass-assignment resistance, Gmail verification reset and notification count, named error bags, password validation/hashing/token rotation/session continuity, JPEG/PNG/WEBP upload, content/type/size/SVG rejection, avatar replacement/removal/idempotency/ownership, and account deletion with credential and filesystem cleanup. The full suite retains registration, login/logout, password reset, and email-verification regression coverage.

## Manual verification checklist

1. Run migrations and create the public storage link.
2. Log in with a verified account and open `/profile` from dashboard.
3. Update username and phone; confirm Gmail remains verified.
4. Change Gmail; confirm redirect to the verification notice and complete the new signed link.
5. Upload each permitted avatar format and confirm replacement/removal.
6. Try a text, SVG, and file larger than 2 MB; confirm rejection.
7. Change password and confirm the current session remains active and future login uses the new password.
8. Try account deletion with a wrong password, then the correct password; confirm logout, file removal, and failed subsequent login.

## Commands

```text
php artisan migrate
php artisan storage:link
php artisan optimize:clear
php artisan route:list
php artisan test
vendor\bin\pint --test
```
