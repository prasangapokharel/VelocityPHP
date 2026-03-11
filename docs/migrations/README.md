# VelocityPHP — Database Migrations

Migrations let you define, version, and reproduce your database schema in code.

---

## Running Migrations

```bash
# Apply all pending migrations (from project root)
php migrate.php

# Check which migrations have run
php migrate.php status
```

Migrations are tracked in the `migrations` table (created automatically on first run).

---

## Migration File Naming

Files live in `database/migrations/` and must match the pattern:

```
NNNN_description.php
```

Where `NNNN` is a zero-padded sequence number. Migrations are applied in ascending order.

Examples:
```
0001_create_users_table.php
0002_remember_tokens.php
0003_create_posts_table.php
```

---

## Creating a Migration

```php
<?php
// database/migrations/0003_create_posts_table.php

use App\Database\MigrationManager;
use App\Database\TableBuilder;

return new class {
    public function up(MigrationManager $m): void
    {
        $m->create('posts', function (TableBuilder $t) {
            $t->id();                                    // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $t->unsignedBigInteger('user_id');           // FK column
            $t->string('title');                         // VARCHAR(255)
            $t->string('slug')->unique();
            $t->text('content');                         // TEXT
            $t->enum('status', ['draft', 'published'])
              ->default('draft');
            $t->integer('views')->default(0);
            $t->timestamps();                            // created_at, updated_at DATETIME
            $t->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('CASCADE');
        });
    }

    public function down(MigrationManager $m): void
    {
        $m->dropIfExists('posts');
    }
};
```

---

## TableBuilder Column Methods

| Method | SQL type | Notes |
|--------|----------|-------|
| `id()` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY` | |
| `string($col, $len = 255)` | `VARCHAR(N)` | |
| `text($col)` | `TEXT` | |
| `integer($col)` | `INT` | |
| `bigInteger($col)` | `BIGINT` | |
| `unsignedBigInteger($col)` | `BIGINT UNSIGNED` | Use for FK columns |
| `boolean($col)` | `TINYINT(1)` | |
| `decimal($col, $p, $s)` | `DECIMAL(p,s)` | |
| `float($col)` | `FLOAT` | |
| `datetime($col)` | `DATETIME` | |
| `date($col)` | `DATE` | |
| `timestamp($col)` | `TIMESTAMP` | |
| `timestamps()` | `created_at DATETIME, updated_at DATETIME` | |
| `enum($col, $values)` | `ENUM(...)` | |
| `json($col)` | `JSON` | MySQL 5.7.8+ |
| `softDeletes()` | `deleted_at DATETIME NULL` | |

### Column modifiers (chained)

```php
$t->string('email')->unique();
$t->integer('age')->nullable();
$t->string('role')->default('user');
$t->string('slug')->unique()->nullable();
```

### Indexes

```php
$t->index('status');                     // Single-column index
$t->index(['user_id', 'created_at']);    // Composite index
$t->unique('email');
```

### Foreign keys

```php
$t->foreign('user_id')
  ->references('id')
  ->on('users')
  ->onDelete('CASCADE')    // CASCADE | SET NULL | RESTRICT | NO ACTION
  ->onUpdate('CASCADE');
```

---

## MigrationManager Methods

| Method | Description |
|--------|-------------|
| `create($table, callable $fn)` | Create a new table |
| `alter($table, callable $fn)` | Alter an existing table |
| `drop($table)` | Drop a table |
| `dropIfExists($table)` | Drop if exists (safe) |
| `addColumn($table, callable $fn)` | Add columns to existing table |
| `dropColumn($table, $column)` | Drop a column |
| `renameColumn($table, $from, $to)` | Rename a column |
| `addIndex($table, $columns, $name)` | Add an index |
| `dropIndex($table, $name)` | Drop an index |

### Alter example

```php
public function up(MigrationManager $m): void
{
    $m->alter('users', function (TableBuilder $t) {
        $t->string('avatar')->nullable();
        $t->integer('post_count')->default(0);
    });
}
```

### Raw SQL fallback

```php
public function up(MigrationManager $m): void
{
    $m->statement("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER email");
}
```

---

## Rollback

Individual rollback is not yet automated via CLI. To roll back a migration manually:

1. Implement the `down()` method in the migration class.
2. Call it manually:

```php
php -r "
require 'migrate.php';
\$m = new App\Database\MigrationManager();
\$migration = require 'database/migrations/0003_create_posts_table.php';
\$migration->down(\$m);
"
```

---

## Existing Migrations

| File | What it creates |
|------|-----------------|
| `0001_create_users_table.php` | `users` table with name, email, password, role, status, last_login |
| `0002_remember_tokens.php` | `remember_tokens` table for persistent login |
