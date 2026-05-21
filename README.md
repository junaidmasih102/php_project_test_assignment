# php_project_test_assignment

Laravel 11 + Livewire 4 (SFC). MySQL.

## Setup

    composer install
    cp .env.example .env
    php artisan key:generate
    # set DB_* in .env
    php artisan migrate --seed
    php artisan serve

`--seed` runs `Database\Seeders\DatabaseSeeder`, which creates the two
default users below (idempotent — safe to re-run). To re-seed without
re-migrating: `php artisan db:seed`.

## Login

Default users (from `DatabaseSeeder`):

- admin@example.com / password — admin
- user@example.com  / password — user

## Routes

- `/login` — auth (guest only)
- `/purchases` — list, any role
- `/purchases/create`, `/purchases/{id}/edit` — admin only

## Roles

`role` column on users. `admin` or `user`.

- admin: create / edit / delete purchases
- user: view only

Checked in three places (defense in depth):

1. `role` middleware on routes — `app/Http/Middleware/EnsureUserHasRole.php`
2. `PurchasePolicy` — `viewAny`, `create`, `update`, `delete`
3. `Gate::authorize()` inside Livewire `mount()` and action methods

## Legacy import

    php artisan legacy:migrate-purchases

Maps `item_name` → `items`, `brand_name` → `brands`, writes into
`purchases` + `purchase_items`. `firstOrCreate` on lookups, dup check on
`(item_id, brand_id, qty, price)` so the command is safe to re-run.

## Notes

- Tailwind via CDN — no `npm run build` required for it to render.
- Livewire 4 bundles Alpine. Don't call `Alpine.start()` from
  `resources/js/app.js` if you wire Vite in later — you'll get duplicate
  Alpine warnings.
- Livewire SFC files live in `resources/views/components/`. Originally
  prefixed with the  emoji per the v4 convention; renaming works too
  (the finder falls back to plain filenames).
