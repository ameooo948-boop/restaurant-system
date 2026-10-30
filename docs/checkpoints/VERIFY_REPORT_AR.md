# Verification Report — Checkpoint 01

## Static checks

- `php -l` executed for every shipped PHP file.
- Result: all shipped PHP files passed syntax validation.
- Full per-file output: `PHP_LINT_REPORT.txt`.

## Not yet executed

The Laravel Feature Tests and real PostgreSQL migrations have not been executed in this workspace because Composer and the Laravel `vendor/` dependencies are not installed here, and no Supabase project credentials were provided. They must be run after the user creates the Laravel project and configures their Supabase connection:

```powershell
php artisan migrate
php artisan test --filter=RestaurantBranchSchemaTest
```

Do not treat this checkpoint as database-verified until these commands succeed on the project environment.
