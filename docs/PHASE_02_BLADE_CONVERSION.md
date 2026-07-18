# Phase 2 — Blade Conversion

## Goal

Convert the existing Persian Tailwind UI into a reusable Laravel 12 Blade frontend. This phase is presentation-only: no authentication, controllers, models, database access, middleware, validation, sessions, or business logic were added.

## Files created

- `resources/views/layouts/app.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/verify.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/components/brand-logo.blade.php`
- `resources/views/components/theme-toggle.blade.php`
- `resources/views/partials/side-panel.blade.php`
- `docs/PHASE_02_BLADE_CONVERSION.md`

The required `resources/views/partials/` directory physically exists and contains the shared desktop side panel.

## Files modified

- `routes/web.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `tests/Feature/ExampleTest.php`

## Blade concepts used

- Layout inheritance with `@extends('layouts.app')`
- Page titles with `@section('title', '...')`
- Page bodies with `@section('content')`
- Reusable anonymous components with `<x-brand-logo />` and `<x-theme-toggle />`
- Reusable partial inclusion with `@include`
- Escaped Blade output in the partial
- `@foreach` for the four presentation-only verification inputs
- `@csrf` in placeholder POST forms

## Layout inheritance

`layouts/app.blade.php` owns the HTML5 document, Persian RTL direction, metadata, title yield, early theme initialization, Vazirmatn font, Vite asset entry points, shared body styles, and `@yield('content')`. The register, verify, and dashboard views contain only page content.

## Components and partial

- `brand-logo`: shared linked logo and Persian brand name, with a light variant for the image panel.
- `theme-toggle`: shared accessible light/dark mode control.
- `partials/side-panel`: shared desktop-only image, overlay, brand, heading, and descriptive copy.

## Responsive decisions

- Main card wrappers use `w-full` with small-screen horizontal padding.
- The decorative image panel uses `hidden lg:block`.
- The mobile brand appears when the desktop panel is hidden.
- `overflow-x-hidden` guards the document boundary.
- Verification inputs use `flex-1 min-w-0` on narrow screens and a fixed maximum width from `sm` upward.
- Forms remain single-column and touch-friendly on mobile.
- Theme preference remains functional and is stored in browser local storage when available.

## Preview routes

| Method | URI | View |
|---|---|---|
| GET/HEAD | `/` | `auth.register` |
| GET/HEAD | `/register` | `auth.register` |
| GET/HEAD | `/verify` | `auth.verify` |
| GET/HEAD | `/dashboard` | `dashboard.index` |

Routes use `Route::view` directly; no controller was created.

## Intentionally not implemented

- Form submission and POST routes
- Authentication and authorization
- Validation and old input/error state
- Database operations
- Verification-code generation, countdown, expiry, resend, or delivery
- Session/token handling
- Dynamic user/dashboard data
- Logout behavior

Forms use `action="#"` and exist only to preserve the frontend structure. Those behaviors belong to later phases.

## Validation commands

```text
php artisan route:list
php artisan view:cache
php artisan test
```

The feature test uses Laravel's `withoutVite()` so HTTP rendering can be tested independently of a running Vite development server or generated production manifest.
