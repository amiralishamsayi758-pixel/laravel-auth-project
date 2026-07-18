# Phase 1 — Laravel 12 Structure and Architecture

> Scope: architecture only. This document describes the existing project; it does not add authentication, controllers, models, database behavior, middleware, validation, sessions, cookies, services, or business logic.

## Project snapshot

- Framework: Laravel Framework 12.64.0
- PHP requirement: `^8.2`
- Application shape: standard minimal Laravel 12 skeleton
- Current application route: `GET|HEAD /`
- Current route action: an inline closure that returns `view('welcome')`
- Current controller involvement: none for `/`
- Existing database/authentication scaffolding is Laravel's default installation content and is inspected only, not used in this phase.

---

## 1. Laravel Overview

Laravel is a PHP web application framework. A framework supplies a standard entry point, object lifecycle, routing, HTTP abstractions, configuration, dependency management, error handling, and conventions for organizing application code. Its central runtime object is `Illuminate\Foundation\Application`. That object is both the application coordinator and Laravel's service container.

The framework code lives in `vendor/laravel/framework`; project-specific code belongs mainly in `app`, `routes`, `resources`, and `config`. Composer connects these layers through autoloading.

Two execution paths share the same application bootstrap:

```text
Web request                         Console command
Browser                            Terminal
   │                                  │
   ▼                                  ▼
public/index.php                    artisan
   │                                  │
   └──────────► bootstrap/app.php ◄────┘
                         │
                         ▼
                 Laravel Application
```

### Core vocabulary

- **Request:** an object representation of incoming HTTP data: method, URL, headers, query values, and body.
- **Response:** the outgoing HTTP status, headers, and body.
- **Router:** matches a request method and path to a route action.
- **Middleware:** pipeline layers around route execution. They may inspect or transform a request/response. This phase implements none.
- **Service container:** creates objects and resolves their dependencies.
- **Service provider:** registers and bootstraps framework/application capabilities.
- **Facade:** a static-looking proxy to an object managed by the container, such as `Route`.
- **Helper:** a globally available convenience function, such as `view()`, `config()`, or `public_path()`.

---

## 2. Laravel Folder Structure

```text
laravel-auth-project/
├── app/          Application-owned PHP classes
├── bootstrap/    Application construction and generated bootstrap caches
├── config/       Configuration arrays
├── database/     Database scaffolding (not used in Phase 1)
├── public/       Web server document root and front controller
├── resources/    Source views, CSS, and JavaScript
├── routes/       HTTP and console route definitions
├── storage/      Runtime-generated files
├── tests/        Automated tests
└── vendor/       Composer-installed framework/dependencies; do not hand-edit
```

### `app/`

Application-owned PHP code, autoloaded under the `App\` namespace.

- `Http/Controllers/Controller.php`: empty base controller supplied by the skeleton. No project controller was created.
- `Models/User.php`: default Eloquent/auth-oriented user class. It exists already; Phase 1 does not use or modify it.
- `Providers/AppServiceProvider.php`: application provider. `register()` is for container registrations; `boot()` runs after providers have registered. Both are empty here.

Laravel 12's minimal skeleton intentionally has fewer folders than older applications. Directories such as `app/Http/Middleware` or `app/Console` may appear only when the application needs them.

### `bootstrap/`

- `app.php`: declaratively configures and creates the application.
- `providers.php`: lists application-specific providers; currently only `AppServiceProvider`.
- `cache/`: machine-generated package/service discovery caches. Laravel/Composer manages these; do not hand-edit them.

“Bootstrap” means preparing the framework to run, not the Bootstrap CSS library.

### `config/`

Each PHP file returns a configuration array. The filename becomes the first segment of a configuration key: `config('app.name')` reads key `name` from `config/app.php`.

- `app.php`: application identity, environment, debug, URL, timezone, locale, encryption, maintenance mode.
- `auth.php`: authentication guards/providers/password settings; inspected only.
- `cache.php`: cache stores and prefixes.
- `database.php`: database connections; no database work is performed here.
- `filesystems.php`: storage disks.
- `logging.php`: log channels.
- `mail.php`: mail transports and sender defaults.
- `queue.php`: queue connections and failed jobs.
- `services.php`: credentials/settings for external services.
- `session.php`: session driver and cookie-related options; not used in this phase.

### `database/`

- `factories/`: test/development data blueprints.
- `migrations/`: version-controlled database schema instructions.
- `seeders/`: database population instructions.
- `database.sqlite`: default SQLite file.

These are default scaffolding. Their presence does not mean the `/` request queries the database. Nothing here is executed or changed in Phase 1.

### `public/`

The only directory that should be exposed as the web server document root.

- `index.php`: the HTTP front controller; nearly every dynamic request enters Laravel here.
- `favicon.ico`: browser/site icon.
- `robots.txt`: crawler instructions.
- Built frontend files may later appear under `public/build`; source assets remain in `resources`.

Exposing the project root instead of `public/` can reveal sensitive files and is a serious deployment mistake.

### `resources/`

- `views/welcome.blade.php`: the default Blade view returned by `/`.
- `css/app.css`: frontend stylesheet source.
- `js/app.js`: frontend JavaScript entry.
- `js/bootstrap.js`: frontend library initialization (not Laravel's PHP bootstrap).

Resources are source inputs. Blade produces HTML; Vite processes frontend assets.

### `routes/`

- `web.php`: browser-facing routes configured as the web route file.
- `console.php`: closure-based console commands and console scheduling definitions.

The health route `/up` is configured directly in `bootstrap/app.php`, so it does not appear in `routes/web.php`. The non-vendor route listing in this inspection displayed only `/`.

### `storage/`

Runtime-writable application data:

- `app/`: application-generated files.
- `framework/`: compiled Blade views and other framework runtime data.
- `logs/`: logs.

The inspected compiled file under `storage/framework/views` is generated from a Blade template. Edit the source view, never its compiled copy. The web server process needs appropriate write access to `storage`.

### `tests/`

- `TestCase.php`: project base class for Laravel-integrated tests.
- `Feature/ExampleTest.php`: sends `GET /` and expects HTTP 200.
- `Unit/ExampleTest.php`: isolated PHPUnit example asserting `true`.

Feature tests can boot Laravel and exercise HTTP behavior. Unit tests ideally test small isolated behavior and need not boot the framework.

### `vendor/`

Composer-installed production and development dependencies, including Laravel, Symfony components, PHPUnit, and their transitive packages. `vendor/autoload.php` is the generated autoloader required by both web and console entry points.

Rules: do not hand-edit `vendor`; do not commit it in a normal Composer project; reproduce it with `composer install` from `composer.lock`. To understand framework internals, reading it is useful, but application changes belong outside it.

---

## 3. Important Files — Annotated Reading

Blank lines in the following files are readability separators and have no runtime effect. Comments explain intent and also have no runtime effect.

### `artisan`, line by line

| Line(s) | Purpose                                                                                                                    |
| ------- | -------------------------------------------------------------------------------------------------------------------------- |
| 1       | `#!/usr/bin/env php` lets Unix-like systems execute the file with PHP. On Windows, `php artisan` explicitly invokes PHP.   |
| 2       | Opens PHP mode.                                                                                                            |
| 4       | Imports Laravel's `Application` class name.                                                                                |
| 5       | Imports Symfony's parser for terminal arguments. Laravel uses Symfony Console internally.                                  |
| 7       | Records the command start time for timing/diagnostics.                                                                     |
| 9–10    | Documents and loads Composer's generated autoloader. Without it, framework and app classes cannot be found.                |
| 12–15   | Loads `bootstrap/app.php`, which returns the configured application/container. The docblock tells analysis tools its type. |
| 17      | Converts CLI arguments into `ArgvInput`, asks the application to handle the command, and receives an exit status.          |
| 19      | Returns that status code to the operating system (`0` normally means success).                                             |

`artisan` is not the framework itself; it is the console front controller.

### `bootstrap/app.php`, line by line

| Line(s) | Purpose                                                                                                   |
| ------- | --------------------------------------------------------------------------------------------------------- |
| 1       | Opens PHP mode.                                                                                           |
| 3–5     | Imports the application builder and configuration types for exceptions and middleware.                    |
| 7       | Starts a fluent builder and sets the project base path to the parent of `bootstrap`.                      |
| 8       | Begins routing configuration.                                                                             |
| 9       | Registers `routes/web.php` as the web route file.                                                         |
| 10      | Registers `routes/console.php` for console commands.                                                      |
| 11      | Asks Laravel to expose its built-in health endpoint at `/up`.                                             |
| 12      | Ends routing arguments.                                                                                   |
| 13–15   | Provides the place to configure middleware. It is intentionally empty; no middleware is implemented here. |
| 16–18   | Provides the place to configure exception handling. It is intentionally empty.                            |
| 19      | `create()` builds and returns the configured `Application`, which is also the service container.          |

This file describes application assembly. It does not handle one specific request itself.

### `public/index.php`, line by line

| Line(s) | Purpose                                                                                                                                            |
| ------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1       | Opens PHP mode.                                                                                                                                    |
| 3–4     | Imports the application and HTTP request types.                                                                                                    |
| 6       | Records request start time.                                                                                                                        |
| 8–11    | If maintenance mode created `storage/framework/maintenance.php`, load it before booting the full app.                                              |
| 13–14   | Loads Composer autoloading from outside the public directory.                                                                                      |
| 16–19   | Loads `bootstrap/app.php` and receives the application/container.                                                                                  |
| 21      | `Request::capture()` builds a Laravel request from PHP/server globals; `handleRequest()` runs it through Laravel and sends the resulting response. |

This is the **front controller pattern**: one small entry file delegates dynamic HTTP work to the framework.

### `routes/web.php`, line by line

| Line(s) | Purpose                                                                                                           |
| ------- | ----------------------------------------------------------------------------------------------------------------- |
| 1       | Opens PHP mode.                                                                                                   |
| 3       | Imports the `Route` facade.                                                                                       |
| 5       | Registers a route that responds to HTTP `GET /` (and Laravel also exposes `HEAD`).                                |
| 5–7     | The route action is an anonymous function/closure, not a controller.                                              |
| 6       | The `view()` helper asks the view system to render `resources/views/welcome.blade.php`; `.blade.php` is inferred. |
| 7       | Closes the callback and route registration.                                                                       |

### `resources/views/welcome.blade.php`, annotated line-by-line regions

This default file is 277 lines, but line 18 alone contains a large minified Tailwind stylesheet and many later lines are SVG path data. Every executable/structural region is explained below; repeated SVG coordinate lines have the same responsibility.

| Line(s) | Purpose                                                                                                                                                                                                                                                          |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1       | Declares HTML5.                                                                                                                                                                                                                                                  |
| 2       | Opens the document and uses `app()->getLocale()` plus `str_replace()` to produce a language tag such as `en`. `{{ }}` safely echoes the result.                                                                                                                  |
| 3–5     | Opens `<head>`, declares UTF-8, and configures responsive viewport behavior.                                                                                                                                                                                     |
| 7       | Reads `app.name` through `config()` and escapes it into the page title; `Laravel` is a fallback.                                                                                                                                                                 |
| 9–11    | Labels and loads the external Instrument Sans font.                                                                                                                                                                                                              |
| 13–16   | If a Vite build manifest or hot-development marker exists, `@vite` emits the correct CSS/JS tags; otherwise the fallback branch runs.                                                                                                                            |
| 17–19   | Contains the large inline fallback stylesheet (line 18 is minified generated CSS), then closes the conditional with `@endif`.                                                                                                                                    |
| 21      | Opens the styled `<body>`. Most class names are presentation utilities, not PHP behavior.                                                                                                                                                                        |
| 22–47   | Conditionally renders a header only if a named `login` route exists. `Route::has()` queries the router; no authentication is created here. Nested checks render login/register links only when those named routes exist.                                         |
| 49–75   | Opens the main welcome layout, introductory text, and links to Laravel documentation/Laracasts. The Blade file supplies HTML; it does not perform a server-side request to those links.                                                                          |
| 77–267  | Draws the decorative Laravel artwork. The light and dark SVG blocks contain groups and `<path>` coordinate data. Each path line defines a shape's geometry, fill, stroke, or transition. These lines are static presentation and do not invoke Laravel services. |
| 268–272 | Adds a decorative overlay, then closes the illustration, `<main>`, and wrapper elements.                                                                                                                                                                         |
| 273–275 | If a `login` route exists, adds a desktop spacing element. Again, checking a route does not create authentication.                                                                                                                                               |
| 276–277 | Closes `<body>` and `<html>`.                                                                                                                                                                                                                                    |

Blade constructs present here:

- `{{ expression }}`: escaped output.
- `@if`, `@else`, `@endif`: template control flow.
- `@vite(...)`: directive integrating Vite-built assets.
- `{{-- ... --}}`: Blade comments, removed from rendered output.

Blade compiles templates into PHP under `storage/framework/views`, then PHP executes that compiled form. Blade is a view layer, not a browser-side template engine.

### `app/Models/User.php`, line by line

This is existing Laravel default scaffolding. Understanding it does not authorize using it in Phase 1.

| Line(s) | Purpose                                                                                                                    |
| ------- | -------------------------------------------------------------------------------------------------------------------------- |
| 1       | Opens PHP mode.                                                                                                            |
| 3       | Places the class in namespace `App\Models`, mapped by Composer to `app/Models`.                                            |
| 5       | Commented optional interface import; it has no runtime effect.                                                             |
| 6–9     | Imports the factory type, `HasFactory` trait, authentication-capable base class, and notifications trait.                  |
| 11      | Declares `User` as a subclass of `Authenticatable`, which itself is an Eloquent model suitable for Laravel authentication. |
| 13–14   | Uses `HasFactory` and `Notifiable`; the generic doc comment helps static analysis understand the factory type.             |
| 16–20   | Documents the next property as the mass-assignable attribute allowlist.                                                    |
| 21–25   | Allows `name`, `email`, and `password` during mass assignment. This is database-oriented behavior and is not used here.    |
| 27–31   | Documents attributes hidden during array/JSON serialization.                                                               |
| 32–35   | Hides `password` and `remember_token` from serialized output. This does not encrypt either value.                          |
| 37–43   | Documents and declares the model's cast definitions method.                                                                |
| 44–47   | Converts `email_verified_at` to a date-time object and applies Laravel's hashed cast when assigning `password`.            |
| 48–50   | Closes the returned array, method, and class.                                                                              |

### `config/app.php`, line by line

The long `/* ... */` blocks explain the setting that follows them. The executable entries are:

| Entry                                         | Purpose                                                                           |
| --------------------------------------------- | --------------------------------------------------------------------------------- |
| `<?php`                                       | Opens PHP mode.                                                                   |
| `return [`                                    | Makes the file return an array to Laravel's configuration repository.             |
| `'name' => env('APP_NAME', 'Laravel')`        | Reads the application name, defaulting to `Laravel`.                              |
| `'env' => env('APP_ENV', 'production')`       | Names the runtime environment. This is not security by itself.                    |
| `'debug' => (bool) env('APP_DEBUG', false)`   | Controls detailed error output; production should use `false`.                    |
| `'url' => env('APP_URL', 'http://localhost')` | Base URL used particularly by console URL generation.                             |
| `'timezone' => 'UTC'`                         | Default PHP/application timezone.                                                 |
| `'locale' => env('APP_LOCALE', 'en')`         | Primary translation locale.                                                       |
| `'fallback_locale' => ...`                    | Locale to use when a translation is missing.                                      |
| `'faker_locale' => ...`                       | Locale for Faker-generated development/test data.                                 |
| `'cipher' => 'AES-256-CBC'`                   | Encryption cipher used by Laravel encryption services.                            |
| `'key' => env('APP_KEY')`                     | Secret encryption key; it must not be committed or shared.                        |
| `'previous_keys' => [...]`                    | Splits optional prior keys so encrypted data can survive controlled key rotation. |
| `'maintenance.driver'`                        | Chooses file- or cache-backed maintenance mode.                                   |
| `'maintenance.store'`                         | Names the cache store when relevant.                                              |
| closing `];`                                  | Ends and returns the configuration array.                                         |

Professional rule: call `env()` in configuration files. Application code should read `config()`, especially because configuration may be cached.

### `composer.json`, field by field

JSON fields are the meaningful equivalent of lines here:

| Field                                                | Purpose                                                                                                                               |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `$schema`                                            | Enables Composer JSON validation/editor support.                                                                                      |
| `name`, `type`, `description`, `keywords`, `license` | Package metadata; this is a project under the MIT license.                                                                            |
| `require.php`                                        | Accepts compatible PHP 8.2+ versions within Composer's constraint rules.                                                              |
| `require.laravel/framework`                          | Requires Laravel 12.x compatible releases. Installed version is 12.64.0.                                                              |
| `require.laravel/tinker`                             | Interactive Laravel-aware PHP shell.                                                                                                  |
| `require-dev`                                        | Development/test tools: Faker, Pail, Pint, Sail, Mockery, Collision, PHPUnit.                                                         |
| `autoload.psr-4`                                     | Maps `App\`, factory, and seeder namespaces to directories.                                                                           |
| `autoload-dev.psr-4`                                 | Maps `Tests\` to `tests/` in development.                                                                                             |
| `scripts.setup`                                      | Convenience setup workflow; notably includes install, key generation, migration, npm install/build. It was **not run** in this phase. |
| `scripts.dev`                                        | Runs server, queue listener, log viewer, and Vite concurrently. It was not run.                                                       |
| `scripts.test`                                       | Clears config cache then runs tests when explicitly invoked.                                                                          |
| `post-autoload-dump`                                 | Lets Laravel process Composer autoload updates and discover packages.                                                                 |
| `post-update-cmd`                                    | Publishes Laravel assets after dependency updates.                                                                                    |
| `post-root-package-install`                          | Creates `.env` from the example if absent.                                                                                            |
| `post-create-project-cmd`                            | Generates a key, creates SQLite if needed, and runs graceful migrations after project creation.                                       |
| `pre-package-uninstall`                              | Gives Laravel a hook before Composer removes a package.                                                                               |
| `extra.laravel.dont-discover`                        | Package auto-discovery exclusion list; currently empty.                                                                               |
| `config.optimize-autoloader`                         | Optimizes class lookup.                                                                                                               |
| `config.preferred-install`                           | Prefers distribution archives over source clones.                                                                                     |
| `config.sort-packages`                               | Keeps dependency declarations sorted.                                                                                                 |
| `config.allow-plugins`                               | Explicitly allows listed Composer plugins.                                                                                            |
| `minimum-stability`, `prefer-stable`                 | Requires stable packages and prefers stable candidates.                                                                               |

`composer.lock` records exact resolved versions. `composer install` follows the lock; `composer update` resolves newer allowed versions and changes it. Composer manages PHP dependencies; Artisan manages Laravel application commands.

### `.env.example`, line by line by setting

This committed template documents required environment keys but should contain no real secrets. Lines beginning `#` are comments/disabled examples; blank lines group topics.

| Setting(s)                                               | Purpose                                                                                                      |
| -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Identity, environment label, encryption key placeholder, debug flag, and base URL.                           |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`  | Language and fake-data locales.                                                                              |
| `APP_MAINTENANCE_DRIVER`, commented store                | Maintenance mode backend.                                                                                    |
| commented `PHP_CLI_SERVER_WORKERS`                       | Optional worker count for the CLI development server.                                                        |
| `BCRYPT_ROUNDS`                                          | Work factor for bcrypt hashing.                                                                              |
| `LOG_*`                                                  | Logging channel composition, deprecation channel, and minimum level.                                         |
| `DB_CONNECTION` and commented `DB_*`                     | Default SQLite selection and examples for a network database. Not used in Phase 1.                           |
| `SESSION_*`                                              | Session persistence/lifetime/path/domain settings. Not used in Phase 1.                                      |
| `BROADCAST_CONNECTION`                                   | Broadcast backend; `log` writes events to logs.                                                              |
| `FILESYSTEM_DISK`                                        | Default filesystem disk.                                                                                     |
| `QUEUE_CONNECTION`                                       | Default queue backend.                                                                                       |
| `CACHE_STORE`, commented prefix                          | Default cache backend and optional key prefix.                                                               |
| `MEMCACHED_HOST`                                         | Memcached server example.                                                                                    |
| `REDIS_*`                                                | Redis client and connection settings.                                                                        |
| `MAIL_*`                                                 | Mail transport/server/sender settings; `log` avoids sending mail.                                            |
| `AWS_*`                                                  | Credentials and bucket configuration placeholders. Real credentials must never enter `.env.example`.         |
| `VITE_APP_NAME="${APP_NAME}"`                            | Exposes a derived name to Vite. Only explicitly `VITE_`-prefixed variables are intended for browser bundles. |

`.env` is machine-specific and normally ignored by Git. `.env.example` is a safe contract/template. Environment variables are inputs; configuration files translate them into typed, structured settings.

---

## 4. Laravel Request Lifecycle

### Actual lifecycle for `http://127.0.0.1:8000/`

```text
Browser: GET /
      │
      ▼
PHP development server (`php artisan serve`)
      │
      ▼
public/index.php (front controller)
      │
      ├── maintenance check
      ├── Composer autoloader
      ▼
bootstrap/app.php
      │
      ├── creates Application / service container
      ├── registers route sources
      ├── configures middleware and exceptions
      └── providers register, then boot during framework bootstrap
      │
      ▼
Request::capture()
      │
      ▼
HTTP kernel/pipeline and framework/web middleware
      │
      ▼
Router matches GET / in routes/web.php
      │
      ▼
Route closure (NO controller in this project)
      │
      ▼
view('welcome') → Blade compiler/view engine
      │
      ▼
Rendered HTML response
      │
      ▼
Middleware unwinds → response sent
      │
      ▼
Browser renders HTML and requests referenced assets/fonts
```

Step by step:

1. The browser opens a TCP/HTTP connection and sends `GET /` to `127.0.0.1:8000`.
2. The development server routes the request to `public/index.php`. In production, Nginx/Apache should point its document root at `public` and rewrite non-file requests to this file.
3. `public/index.php` checks for file-based maintenance mode.
4. It requires `vendor/autoload.php`, allowing PHP to locate Laravel and application classes through Composer.
5. It requires `bootstrap/app.php`. The returned `Application` is the service container and central framework coordinator.
6. The builder tells Laravel where web/console routes live, establishes `/up`, and provides middleware/exception configuration callbacks.
7. During bootstrapping, Laravel loads configuration, registers service providers, then boots them. Providers bind and initialize framework services such as routing and views.
8. `Request::capture()` converts PHP globals into an `Illuminate\Http\Request` object.
9. `handleRequest()` sends that request through Laravel's HTTP handling pipeline. Middleware conceptually wraps later work: request-side order goes inward; response-side order returns outward.
10. The router compares the HTTP method and normalized path with its route collection. It selects `Route::get('/')` from `routes/web.php`.
11. Laravel invokes the route closure. There is no controller resolution for this route.
12. `view('welcome')` resolves the view factory from the container, locates the Blade template, compiles it when necessary, evaluates it, and produces rendered content.
13. Laravel converts the result into an HTTP response (normally status `200`, content type HTML).
14. The response passes back outward through the middleware pipeline and is sent to the browser.
15. The browser parses and renders the HTML. Referenced fonts or built assets cause separate HTTP requests, each with its own lifecycle.

### Conventional controller-based variation (not implemented here)

```text
Router → Controller action → application coordination → View/Response
```

The service container can instantiate a controller and inject its declared dependencies. This project deliberately stops at a route closure for `/`; the controller box is a common option, not a mandatory lifecycle stage.

### Request and Response

`Illuminate\Http\Request` wraps Symfony's request object and gives Laravel-friendly access to method, path, headers, and input. It should be treated as an HTTP boundary object.

A response carries:

- a status code such as `200` or `404`;
- headers such as `Content-Type`;
- a body such as rendered HTML or JSON.

Laravel can normalize strings, arrays, views, and explicit response objects returned by route actions into a valid response.

---

## 5. Laravel MVC

MVC separates responsibilities; it is a design pattern, not the complete Laravel lifecycle.

```text
                  user HTTP request
                         │
                         ▼
                 Controller / route action
                    │              │
          coordinates              │ selects data for
                    ▼              ▼
                 Model           View (Blade)
           domain/data state       │
                    │              │ renders
                    └──── result ──┘
                                   ▼
                              HTTP response
```

- **Model:** represents domain/data state and behavior. In Laravel, Eloquent models often represent database records, but “model” in MVC is broader than Eloquent.
- **View:** presents data. Blade should focus on rendering, not business decisions or data access.
- **Controller:** receives routed work, coordinates collaborators, and returns a response. A controller should remain thin.

For the current `/` route:

- Model: none
- Controller: none; the closure plays the tiny action role
- View: `welcome.blade.php`

Do not force every trivial route through all three boxes, and do not confuse MVC with Laravel's deeper container/provider/middleware architecture.

---

## 6. Service Container

The container is an object factory and registry. It knows how to:

- construct concrete classes through reflection;
- map an interface/abstract name to a concrete implementation;
- control shared instances (singletons);
- call methods while resolving their dependencies.

Conceptual example only:

```text
Class asks for Interface
          │
          ▼
 Service Container checks binding
          │
          ▼
 Builds concrete implementation
          │
          ▼
 Injects ready object
```

Laravel can automatically resolve a concrete class whose constructor dependencies are themselves resolvable. An interface has no constructible body, so it normally needs an explicit binding, often registered in a service provider.

The container is valuable because consumers depend on capabilities, while object-construction decisions stay centralized. Avoid turning it into a global bag fetched manually everywhere; constructor injection makes dependencies visible.

### Service Providers

Providers are Laravel's main bootstrap mechanism.

- `register()`: register bindings/configuration; avoid relying on services that may not yet be booted.
- `boot()`: run after all providers have registered; appropriate for framework integrations that need registered services.

This project lists `App\Providers\AppServiceProvider` in `bootstrap/providers.php`. Framework/package providers are discovered and cached through Laravel/Composer mechanisms.

---

## 7. Dependency Injection

Dependency injection means an object receives collaborators from outside rather than constructing or globally locating them itself.

```php
// Concept only; not added to this project.
final class ReportReader
{
    public function __construct(private Clock $clock) {}
}
```

The dependency (`Clock`) is explicit in the constructor. The container can resolve and inject it when a binding exists.

Benefits:

- dependencies are visible;
- implementations can be replaced;
- tests can supply controlled substitutes;
- construction logic is not duplicated.

Prefer constructor injection for required long-lived dependencies. Method injection is useful for dependencies tied to one operation, such as an HTTP `Request` in a route/controller method.

### Facades versus injection

`Route::get(...)` looks static, but `Route` is a facade proxy that resolves the router from Laravel's container. Facades are concise and test-aware, but extensive facade use can hide dependencies. Injection is clearer when a class genuinely depends on a collaborator.

### Helpers

Helpers are concise functions:

- `view('welcome')`: create/render a view response candidate.
- `config('app.name')`: read configuration.
- `app()`: access the application/container (or resolve a binding).
- `public_path('...')`: build an absolute path beneath `public`.

Use helpers when they improve clarity. Avoid using `app()` as a service-locator escape hatch inside every class.

---

## 8. Blade Overview

Blade is Laravel's server-side templating engine. A request renders Blade before HTML reaches the browser.

```text
welcome.blade.php
       │ Blade compiles
       ▼
generated PHP in storage/framework/views
       │ PHP executes with view data
       ▼
HTML string
       │ Response
       ▼
Browser
```

Key ideas:

- `{{ $value }}` escapes HTML by default and helps prevent output injection.
- `{!! $value !!}` is unescaped output and is dangerous for untrusted data.
- Directives such as `@if`, `@foreach`, and `@vite` compile to PHP.
- Layouts/components reduce duplication.
- Views should present already-prepared data, not query databases or contain business logic.
- Compiled views are caches, not source files.

---

## 9. Artisan Commands

Artisan is Laravel's console interface, entered through the root `artisan` file.

| Command                                      | When and why it is used                                                                                                                      | Phase 1 status             |
| -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------- |
| `php artisan serve`                          | Starts a local development HTTP server, usually at `127.0.0.1:8000`. Not a production server.                                                | Explained, not started     |
| `php artisan about`                          | Displays framework, environment, cache, and driver information for diagnosis.                                                                | Read-only subset executed  |
| `php artisan route:list`                     | Shows registered methods, URIs, names, actions, and middleware. Useful when debugging routing.                                               | Read-only command executed |
| `php artisan make:controller NameController` | Generates a controller skeleton when HTTP actions need a controller.                                                                         | Not executed               |
| `php artisan make:model Name`                | Generates an Eloquent model; options can generate related artifacts.                                                                         | Not executed               |
| `php artisan make:migration description`     | Generates a timestamped schema-change file.                                                                                                  | Not executed               |
| `php artisan make:request NameRequest`       | Generates a Form Request class for authorization/validation.                                                                                 | Not executed               |
| `php artisan make:middleware Name`           | Generates middleware for HTTP pipeline concerns. Registration/configuration is separate.                                                     | Not executed               |
| `php artisan migrate`                        | Runs pending database migrations against the configured connection. Review environment and migration plan first.                             | **Not executed**           |
| `php artisan optimize:clear`                 | Clears Laravel's generated optimization caches (config, route, view, event, etc.) when stale caches affect development/deployment diagnosis. | Not executed               |

Other useful read-only discovery:

- `php artisan list`: all available commands.
- `php artisan help route:list`: options and arguments for one command.
- `php artisan --version`: installed framework version.

Generator commands create files; migration commands change database state; cache-clearing commands change runtime cache state. Understand a command's side effects before running it.

---

## 10. Environment Files

```text
Operating-system variables / .env
                 │ read during bootstrap
                 ▼
             env('KEY')
                 │ used by config/*.php
                 ▼
       Laravel configuration repository
                 │
                 ▼
          config('file.key')
```

- `.env`: local, environment-specific values; may contain secrets; do not commit it.
- `.env.example`: committed key template with safe example values.
- Real production secrets should be supplied securely by the hosting environment/secret manager.
- `APP_DEBUG=true` in production can expose sensitive context.
- `APP_KEY` protects Laravel encryption and must be unique, strong, and stable. Rotation requires planning.
- Values are strings at the environment boundary; configuration can cast them, as `config/app.php` does for debug.
- After configuration is cached, `.env` is not loaded in the usual way for requests; therefore application code should use `config()` rather than direct `env()` calls.

This inspection did not print the project's real `.env`; only `.env.example` was documented.

---

## 11. Configuration Files

Configuration is Laravel's structured settings layer. Use dot notation: `config('app.name')`, `config('logging.default')`.

Professional practices:

- keep environment differences in environment inputs;
- keep defaults and structure in `config/*.php`;
- never commit secrets;
- read configuration through `config()` outside config files;
- after changing cached configuration, rebuild/clear it deliberately as part of deployment;
- do not confuse “configuration cache” with application data cache.

The inspected `php artisan about` reports database-backed defaults for cache, queue, and session because that is the current environment configuration. Their configured presence does not mean the home route uses them, and no related operation was performed.

---

## 12. Best Practices

1. Point the web server only at `public/`.
2. Keep controllers/route actions thin and views presentation-focused.
3. Make dependencies explicit through injection.
4. Put container bindings and application bootstrap integration in providers.
5. Use configuration as the boundary around environment values.
6. Never edit `vendor`, generated bootstrap caches, or compiled Blade files.
7. Commit `composer.json` and `composer.lock`; use reproducible installs.
8. Inspect routes with `route:list` before assuming what the application exposes.
9. Distinguish source (`resources`) from generated/public output (`public/build`) and runtime cache (`storage`).
10. Learn the actual request path before adding abstractions.
11. Keep secrets out of Git and disable detailed debug output in production.
12. Read generated commands/files before running or accepting their side effects.

---

## 13. Common Beginner Mistakes

- Treating Laravel as “magic” instead of following entry point → bootstrap → container → router → action → response.
- Assuming every request uses a controller. The current home route does not.
- Assuming MVC explains all Laravel internals. Providers, container, middleware, and HTTP handling sit around MVC.
- Editing `vendor` or compiled files because an error points there.
- Serving the project root instead of `public`.
- Calling `env()` throughout application code.
- Committing `.env` or credentials.
- Running `composer update` when a reproducible `composer install` was intended.
- Running generators or migrations without reading their output and understanding the environment.
- Putting queries or business decisions in Blade.
- Using facades/helpers everywhere until class dependencies become invisible.
- Confusing `bootstrap/app.php` with `resources/js/bootstrap.js` or the Bootstrap CSS framework.
- Believing a configured database/session/cache driver proves it was used by a request.
- Clearing caches reflexively instead of understanding which cache is stale.

---

## 14. Recommended Learning Order

1. PHP fundamentals: namespaces, classes, interfaces, traits, closures, exceptions, type declarations.
2. Composer: PSR-4 autoloading, `composer.json` versus `composer.lock`, install versus update.
3. HTTP fundamentals: methods, URLs, status codes, headers, request and response.
4. Laravel entry points: `public/index.php` and `artisan`.
5. Application assembly: `bootstrap/app.php` and providers.
6. Service container and dependency injection.
7. Routing and route inspection.
8. Middleware as a wrapping pipeline (concept only at this phase).
9. Responses and views.
10. Blade escaping, directives, layouts, and components.
11. MVC boundaries and thin controllers.
12. Configuration/environment handling and cache implications.
13. Automated testing boundaries: unit versus feature.
14. Only then begin Phase 2 business features and persistence concepts.

---

# بخش آموزش فارسی — توضیح ساده برای شروع

## لاراول چیست؟

لاراول یک چارچوب برای برنامه‌های PHP است. چارچوب یعنی یک مسیر و نظم آماده به ما می‌دهد تا هر فایل را جای درست بگذاریم. خود لاراول درخواست مرورگر را می‌گیرد، مسیر مناسب را پیدا می‌کند، کد لازم را اجرا می‌کند و پاسخ را برمی‌گرداند.

در این پروژه، وقتی صفحه‌ی اصلی را باز می‌کنیم، هنوز کنترلر یا منطق کاری نداریم. فقط یک Route داریم که فایل نمای `welcome` را برمی‌گرداند.

```text
مرورگر
   │  درخواست صفحه اصلی
   ▼
public/index.php
   │  شروع لاراول
   ▼
bootstrap/app.php
   │  ساخت برنامه
   ▼
routes/web.php
   │  پیدا کردن مسیر /
   ▼
welcome.blade.php
   │  ساخت HTML
   ▼
پاسخ به مرورگر
```

## پوشه‌ها با زبان ساده

- `app/`: کلاس‌هایی که خود برنامه‌نویس برای برنامه می‌نویسد. فعلاً فقط فایل‌های پیش‌فرض را دارد.
- `bootstrap/`: لاراول را آماده‌ی اجرا می‌کند. منظورش کتابخانه‌ی ظاهری Bootstrap نیست.
- `config/`: تنظیمات مرتب برنامه؛ مثلاً نام برنامه و زبان.
- `database/`: فایل‌های مربوط به ساختار و داده‌ی دیتابیس. در این فاز به آن دست نمی‌زنیم.
- `public/`: درِ ورودی وب‌سایت. وب‌سرور باید فقط این پوشه را عمومی کند.
- `resources/`: فایل‌های خام ظاهر برنامه؛ Blade، CSS و JavaScript.
- `routes/`: آدرس‌های برنامه و کاری که برای هر آدرس انجام می‌شود.
- `storage/`: فایل‌های موقت و تولیدشده مثل لاگ و Blade کامپایل‌شده.
- `tests/`: بررسی خودکار درست کار کردن برنامه.
- `vendor/`: کد لاراول و کتابخانه‌هایی که Composer نصب کرده است. این پوشه را دستی تغییر نمی‌دهیم.

## درخواست صفحه اصلی دقیقاً چه می‌شود؟

فرض کنید در مرورگر می‌نویسیم `http://127.0.0.1:8000`:

1. مرورگر درخواست `GET /` می‌فرستد.
2. سرور محلی درخواست را به `public/index.php` می‌دهد.
3. این فایل بررسی می‌کند برنامه در حالت تعمیر نباشد.
4. سپس Autoloader کامپوزر را بار می‌کند تا PHP کلاس‌ها را پیدا کند.
5. فایل `bootstrap/app.php` برنامه‌ی لاراول را می‌سازد.
6. لاراول تنظیمات و Providerها را آماده می‌کند.
7. Request ساخته می‌شود؛ یعنی اطلاعات درخواست مرورگر داخل یک شیء منظم قرار می‌گیرد.
8. Router داخل Routeها دنبال روش `GET` و آدرس `/` می‌گردد.
9. در `routes/web.php` مسیر پیدا می‌شود.
10. Closure همان Route تابع `view('welcome')` را صدا می‌زند. در این پروژه کنترلری در این مسیر نیست.
11. Blade فایل `resources/views/welcome.blade.php` را به HTML تبدیل می‌کند.
12. لاراول یک Response با کد معمولاً `200` می‌سازد و به مرورگر می‌فرستد.

## MVC خیلی ساده

```text
                 Controller
                /          \
               ▼            ▼
 Model (داده/رفتار)      View (نمایش)
               \            /
                └── نتیجه ──┘
```

- **Model:** اطلاعات و رفتار اصلی موضوعات برنامه را نشان می‌دهد. معمولاً با دیتابیس هم ارتباط دارد.
- **View:** چیزی است که کاربر می‌بیند. در لاراول معمولاً Blade است.
- **Controller:** درخواست را هماهنگ می‌کند؛ تصمیم می‌گیرد از چه بخش‌هایی کمک بگیرد و چه پاسخی بدهد.

مثال ساده: در صفحه‌ی فهرست کتاب‌ها، Model می‌تواند مفهوم کتاب را نشان دهد، Controller اطلاعات لازم را هماهنگ کند و View آن‌ها را به شکل HTML نمایش دهد. این فقط مثال آموزشی است و در پروژه ساخته نشده است.

## Service Container چیست؟

Container را مثل مسئول آماده‌کردن ابزارها تصور کنید. اگر یک کلاس برای کار کردن به ابزار دیگری نیاز داشته باشد، Container می‌تواند آن ابزار را بسازد و تحویل بدهد.

```text
کلاس می‌گوید: «من این ابزار را لازم دارم»
                    │
                    ▼
Container ابزار مناسب را پیدا و آماده می‌کند
                    │
                    ▼
ابزار آماده به کلاس داده می‌شود
```

فایده‌اش این است که هر کلاس مجبور نیست خودش همه‌چیز را بسازد. همچنین وابستگی‌ها واضح‌تر و تست کردن آسان‌تر می‌شود.

## Dependency Injection چیست؟

یعنی وسیله‌ای که یک کلاس لازم دارد از بیرون به آن داده شود. مثلاً به‌جای اینکه کلاس گزارش خودش ساعت سیستم را بسازد، یک «ساعت» آماده دریافت کند. این کار وابستگی را آشکار می‌کند. لاراول معمولاً با Container این تزریق را انجام می‌دهد.

## Service Provider چیست؟

Provider به لاراول می‌گوید سرویس‌های برنامه چطور ثبت و آماده شوند:

- `register()`: معرفی کردن سرویس‌ها به Container.
- `boot()`: انجام تنظیمات نهایی بعد از ثبت شدن Providerها.

Provider پیش‌فرض پروژه در `app/Providers/AppServiceProvider.php` است و فعلاً متدهایش خالی هستند.

## Facade و Helper

Facade ظاهری شبیه فراخوانی static دارد، اما پشت صحنه سرویس واقعی را از Container می‌گیرد. `Route::get()` یک نمونه است.

Helper یک تابع کوتاه و آماده است:

- `view('welcome')`: نمای welcome را آماده می‌کند.
- `config('app.name')`: نام برنامه را از تنظیمات می‌خواند.
- `public_path(...)`: مسیر کامل یک فایل داخل `public` را می‌سازد.

این ابزارها مفیدند، ولی استفاده‌ی زیاد و بی‌قاعده می‌تواند وابستگی‌های واقعی کلاس را پنهان کند.

## Blade چیست؟

Blade موتور قالب لاراول است. Blade روی سرور اجرا می‌شود، نه داخل مرورگر.

- `{{ $value }}` مقدار را با Escape امن نمایش می‌دهد.
- `@if` شرط می‌سازد.
- `@foreach` تکرار می‌سازد.
- `@vite` فایل‌های CSS و JavaScript ساخته‌شده با Vite را وصل می‌کند.

Blade ابتدا به PHP تبدیل می‌شود و نسخه‌ی تولیدشده در `storage/framework/views` قرار می‌گیرد. آن فایل تولیدشده را تغییر نمی‌دهیم؛ همیشه فایل اصلی داخل `resources/views` را می‌خوانیم یا ویرایش می‌کنیم.

## Request و Response

Request مثل پاکت نامه‌ای است که مرورگر برای سرور می‌فرستد: آدرس، روش درخواست و Headerها داخل آن هستند. Response پاکتی است که سرور برمی‌گرداند: کد وضعیت، Headerها و متن HTML داخل آن است.

مثال:

```text
Request:  GET /
Response: 200 + Content-Type: text/html + HTML page
```

## Artisan و Composer چه فرقی دارند؟

- **Composer** کتابخانه‌های PHP را مدیریت می‌کند و Autoloading را می‌سازد.
- **Artisan** فرمان‌های مخصوص برنامه‌ی لاراول را اجرا می‌کند.

`composer install` وابستگی‌ها را طبق `composer.lock` نصب می‌کند. `php artisan route:list` مسیرهای لاراول را نشان می‌دهد. این دو ابزار نقش یکسان ندارند.

## Environment و Configuration

`.env` مقدارهای مخصوص همان کامپیوتر یا سرور را نگه می‌دارد و ممکن است راز داشته باشد. آن را Commit نمی‌کنیم. `.env.example` فقط یک نمونه‌ی امن از نام تنظیمات لازم است.

فایل‌های `config/` مقدارهای `.env` را می‌خوانند و به شکل مرتب در اختیار برنامه می‌گذارند. داخل کد برنامه بهتر است از `config()` استفاده کنیم، نه `env()`.

مثال مسیر خواندن نام برنامه:

```text
.env: APP_NAME
       │
       ▼
config/app.php: env('APP_NAME', 'Laravel')
       │
       ▼
config('app.name')
       │
       ▼
عنوان صفحه در Blade
```

## اشتباه‌های رایج برای تازه‌کار

- تغییر دادن فایل‌های `vendor` یا فایل Blade کامپایل‌شده.
- عمومی کردن کل پوشه‌ی پروژه به‌جای `public`.
- گذاشتن رمز و کلید واقعی در Git.
- فکر کردن اینکه همه‌ی Routeها حتماً Controller دارند.
- گذاشتن منطق کاری یا دسترسی دیتابیس داخل View.
- اجرای Migration یا Generator بدون فهم اثر آن.
- یکی دانستن Composer و Artisan.
- استفاده از `env()` در همه‌جای برنامه.
- فکر کردن اینکه وجود تنظیم دیتابیس یعنی هر درخواست از دیتابیس استفاده می‌کند.

## ترتیب پیشنهادی یادگیری

1. PHP شیءگرا، Namespace، Interface، Trait و Closure.
2. Composer و PSR-4 Autoloading.
3. HTTP: روش‌ها، آدرس، Status Code، Header، Request و Response.
4. `public/index.php` و `artisan`.
5. `bootstrap/app.php` و Service Provider.
6. Service Container و Dependency Injection.
7. Route و دستور `route:list`.
8. مفهوم Pipeline و Middleware، بدون ساختن آن در این فاز.
9. Response، View و Blade.
10. مرزهای MVC و دلیل ساده نگه داشتن Controller.
11. `.env`، Configuration و Cache تنظیمات.
12. تفاوت Unit Test و Feature Test.

---

## Phase 1 Inspection Record

### Files and areas inspected

- Root project manifest/files: `artisan`, `composer.json`, `composer.lock` metadata, `.env.example`, project directory listing
- `app/`: controller base, default `User` model, `AppServiceProvider`
- `bootstrap/`: `app.php`, `providers.php`, generated cache inventory
- `config/`: complete configuration-file inventory and detailed `app.php` review
- `database/`: factories, migrations, seeders, SQLite file inventory only
- `public/`: `index.php` and public asset inventory
- `resources/`: Blade, CSS, and JavaScript inventory; complete structural review of the 277-line welcome view
- `routes/`: `web.php`, `console.php`
- `storage/`: runtime directory/file inventory, including the presence of a compiled Blade view
- `tests/`: base, feature example, and unit example
- `vendor/`: top-level dependency namespaces, Composer autoloader role, and installed Laravel package metadata

### File created

- `docs/PHASE_01_LARAVEL_STRUCTURE.md` (this document)

### Commands executed

Read-only commands only:

```text
Get-ChildItem ...
rg --files ...
Get-Content ...
php artisan --version
composer show laravel/framework --no-ansi
php artisan about --only=environment --only=drivers
php artisan route:list --except-vendor
git status --short
```

`git status` reported that this directory is not a Git repository. No server, generator, migration, test, package installation, cache mutation, authentication, database action, or business logic command was run.

### Project summary

This is a clean minimal Laravel 12.64.0 application skeleton. Its only application web route is `/`, implemented by a closure that renders the default welcome Blade view. Laravel is assembled through `bootstrap/app.php`; Composer autoloading connects application and vendor classes; `public/index.php` handles HTTP entry and `artisan` handles console entry. Default model/database/auth-related scaffolding exists but is not part of the current home request and was untouched.

### Learning summary

The key mental model is: entry point loads Composer, bootstrap creates the application/container, providers register and boot services, the request enters a middleware pipeline, the router selects an action, the action returns a value, Laravel normalizes it into a response, and middleware unwinds before the response reaches the browser. MVC describes organization inside this wider lifecycle; it is not the whole framework.

### Topics to master before Phase 2

- PHP OOP, namespaces, interfaces, traits, closures, and type declarations
- Composer autoloading and dependency locking
- HTTP request/response fundamentals
- Laravel web and console entry points
- `bootstrap/app.php` and service-provider phases
- Service container bindings and automatic resolution
- Constructor and method dependency injection
- Routing, named routes, and route inspection
- Conceptual middleware pipeline order
- Blade compilation, escaping, and view boundaries
- MVC responsibilities and thin HTTP actions
- `.env` versus configuration and configuration caching
- Source files versus generated/cache/vendor files
- Unit versus feature test boundaries

Only after these concepts are comfortable should Phase 2 introduce application behavior.
