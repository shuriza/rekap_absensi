# Copilot Instructions for rekap_absensi

## Project Overview
- This is a Laravel-based web application for managing and reporting employee attendance (absensi), including features for holidays, leave (izin), and exporting reports.
- The frontend uses Blade templates, Tailwind CSS, jQuery, DataTables, and Flatpickr for UI/UX.
- Key business logic is in `app/` (Controllers, Models, Exports), with views in `resources/views/`.

## Key Components & Structure
- `app/Http/Controllers/` — Laravel controllers for handling routes and business logic.
- `app/Models/` — Eloquent models for `Absensi`, `Karyawan`, `IzinPresensi`, etc.
- `app/Exports/` — Excel export logic (see `RekapAbsensiBulananExport.php`).
- `resources/views/absensi/rekap.blade.php` — Main attendance recap UI, including modals and custom JS for izin input.
- `routes/web.php` — Main route definitions.
- `database/migrations/` — Table definitions for absensi, karyawan, izin, holidays, etc.

## Developer Workflows
- **Install dependencies:**
  - `composer install` (PHP)
  - `npm install` (JS/CSS)
- **Environment setup:**
  - Copy `.env.example` to `.env` and configure DB.
  - `php artisan key:generate`
- **Build assets:**
  - `npm run dev` (keep running for hot reload)
- **Database:**
  - `php artisan migrate --seed` (initial setup)
- **Run server:**
  - `php artisan serve` (keep running for local dev)

## Project-Specific Patterns
- **Custom modals and alerts** are implemented in Blade with Tailwind classes, not browser defaults. See `rekap.blade.php` for notification patterns.
- **Attendance table** is rendered server-side, but interactivity (modals, alerts, DataTables) is handled via inline JS in Blade views.
- **Data attributes** (`data-*`) on table cells are used to pass context to JS handlers (e.g., for opening modals with correct data).
- **Export** uses dedicated routes and Exports in `app/Exports/`.

## Integration & Conventions
- Uses Laravel's resourceful routing and Eloquent ORM.
- Follows Laravel's Blade and asset pipeline conventions.
- JS/CSS assets are managed via Vite and Tailwind (see `vite.config.js`, `tailwind.config.js`).
- All modals and notifications should use Tailwind-based components for consistency.

## Examples
- To add a new attendance type or status, update both the model logic and the Blade view's table rendering.
- For new export formats, add a new Export class in `app/Exports/` and register a route/controller method.

---
If you are unsure about a workflow or pattern, check the README.md or look for examples in `resources/views/absensi/rekap.blade.php` and `app/Exports/`.
