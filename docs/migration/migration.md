# VelocityPhp Database Migrations

Complete guide to database migrations in VelocityPhp framework.

---

## Table of Contents

1. [Overview](#overview)
2. [Migration Structure](#migration-structure)
3. [Creating Migrations](#creating-migrations)
4. [Running Migrations](#running-migrations)
5. [Schema Builder](#schema-builder)
6. [Column Types](#column-types)
7. [Indexes & Constraints](#indexes--constraints)
8. [Best Practices](#best-practices)

---

## Overview

Migrations are version control for your database. They allow you to:

- Define database schema in PHP code
- Track changes over time
- Share schema with team members
- Roll back changes when needed
- Deploy consistently across environments

### File Location

```
database/
├── migrations/
│   ├── 2024_01_01_000001_CreateUsersTable.php
│   ├── 2024_01_02_000001_CreatePostsTable.php
│   └── 2024_01_03_000001_AddAvatarToUsers.php
├── schema.sql          # Full schema reference
└── Seeder.php          # Data seeding (legacy)
```

---

## Migration Structure

### Naming Convention

```
{YYYY}_{MM}_{DD}_{HHMMSS}_{DescriptiveName}.php
```

Examples:
- `2024_01_01_000001_CreateUsersTable.php`
- `2024_06_15_143022_AddStatusToOrders.php`
- `2024_12_01_000001_CreatePaymentsTable.php`

### Basic Template

```php
<?php
/**
 * Migration: Create Users Table
 * 
 * @package VelocityPhp
 */

namespace App\Database\Migrations;

use App\Database\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migration (create/modify)
     */
    public function up(): void
    {
        $this->create('users', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->enum('role', ['user', 'admin', 'moderator'])->default('user');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
            
            $table->index('email');
            $table->index('status');
        });
    }
    
    /**
     * Reverse the migration (drop/revert)
     */
    public function down(): void
    {
        $this->drop('users');
    }
}
```

---

## Creating Migrations

### Step 1: Create Migration File

Create a new file in `database/migrations/`:

```php
<?php
// database/migrations/2024_01_15_000001_CreateProductsTable.php

namespace App\Database\Migrations;

use App\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->create('products', function ($table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        $this->drop('products');
    }
}
```

### Step 2: Register Migration

Add to migration runner or run manually.

### Common Migration Types

#### Create Table
```php
public function up(): void
{
    $this->create('table_name', function ($table) {
        // Define columns
    });
}
```

#### Modify Table (Add Column)
```php
public function up(): void
{
    $this->table('users', function ($table) {
        $table->string('avatar', 255)->nullable()->after('email');
    });
}

public function down(): void
{
    $this->table('users', function ($table) {
        $table->dropColumn('avatar');
    });
}
```

#### Drop Table
```php
public function up(): void
{
    $this->drop('old_table');
}

public function down(): void
{
    $this->create('old_table', function ($table) {
        // Recreate structure
    });
}
```

#### Rename Table
```php
public function up(): void
{
    $this->rename('old_name', 'new_name');
}

public function down(): void
{
    $this->rename('new_name', 'old_name');
}
```

---

## Running Migrations

### Manual Execution

Currently, migrations are run via direct SQL or PHP execution:

```php
<?php
// run-migrations.php

require_once 'src/utils/Autoloader.php';
App\Utils\Autoloader::register();

// Define constants
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', __DIR__ . '/src/config');

// Get all migration files
$migrations = glob('database/migrations/*.php');
sort($migrations);

foreach ($migrations as $file) {
    require_once $file;
    
    // Extract class name from filename
    $filename = basename($file, '.php');
    $parts = explode('_', $filename);
    $className = end($parts);
    $fullClass = "App\\Database\\Migrations\\{$className}";
    
    if (class_exists($fullClass)) {
        $migration = new $fullClass();
        $migration->up();
        echo "Migrated: {$filename}\n";
    }
}
```

### Using Schema SQL

For quick setup, import the full schema:

```bash
mysql -u root -p native < database/schema.sql
```

---

## Schema Builder

### Table Creation Methods

```php
// Create new table
$this->create('table', function ($table) { });

// Modify existing table
$this->table('table', function ($table) { });

// Drop table
$this->drop('table');

// Drop if exists
$this->dropIfExists('table');

// Rename table
$this->rename('old', 'new');

// Check if table exists
$this->hasTable('table');

// Check if column exists
$this->hasColumn('table', 'column');
```

### Raw SQL

```php
public function up(): void
{
    $this->execute("
        CREATE TABLE custom_table (
            id INT PRIMARY KEY AUTO_INCREMENT,
            data JSON
        ) ENGINE=InnoDB
    ");
}
```

---

## Column Types

### Numeric Types

```php
$table->id();                           // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
$table->bigInteger('votes');            // BIGINT
$table->integer('count');               // INT
$table->mediumInteger('count');         // MEDIUMINT
$table->smallInteger('count');          // SMALLINT
$table->tinyInteger('count');           // TINYINT
$table->unsignedInteger('count');       // INT UNSIGNED
$table->decimal('amount', 10, 2);       // DECIMAL(10,2)
$table->float('rate');                  // FLOAT
$table->double('rate');                 // DOUBLE
$table->boolean('active');              // TINYINT(1)
```

### String Types

```php
$table->string('name', 100);            // VARCHAR(100)
$table->char('code', 10);               // CHAR(10)
$table->text('description');            // TEXT
$table->mediumText('content');          // MEDIUMTEXT
$table->longText('content');            // LONGTEXT
$table->json('metadata');               // JSON
```

### Date/Time Types

```php
$table->date('birth_date');             // DATE
$table->dateTime('published_at');       // DATETIME
$table->time('start_time');             // TIME
$table->timestamp('created_at');        // TIMESTAMP
$table->timestamps();                   // created_at + updated_at
$table->year('graduation_year');        // YEAR
```

### Binary Types

```php
$table->binary('data');                 // BLOB
$table->mediumBinary('data');           // MEDIUMBLOB
$table->longBinary('data');             // LONGBLOB
```

### Special Types

```php
$table->enum('status', ['draft', 'published', 'archived']);
$table->set('permissions', ['read', 'write', 'delete']);
$table->uuid('uuid');                   // CHAR(36)
$table->ipAddress('ip');                // VARCHAR(45)
$table->macAddress('mac');              // VARCHAR(17)
```

---

## Column Modifiers

```php
$table->string('name')->nullable();              // Allow NULL
$table->string('role')->default('user');         // Default value
$table->integer('order')->unsigned();            // UNSIGNED
$table->string('email')->unique();               // UNIQUE constraint
$table->integer('id')->primary();                // PRIMARY KEY
$table->integer('user_id')->index();             // Add index
$table->text('bio')->comment('User biography');  // Column comment
$table->string('avatar')->after('email');        // Position after column
$table->string('temp')->first();                 // Position first
$table->integer('count')->autoIncrement();       // AUTO_INCREMENT
```

### Chaining Modifiers

```php
$table->string('email', 100)
    ->unique()
    ->nullable()
    ->comment('User email address');
```

---

## Indexes & Constraints

### Creating Indexes

```php
// Single column index
$table->index('email');

// Multi-column index
$table->index(['user_id', 'created_at']);

// Named index
$table->index('email', 'users_email_index');

// Unique index
$table->unique('email');
$table->unique(['user_id', 'post_id']);

// Fulltext index (MySQL)
$table->fulltext('content');

// Primary key
$table->primary('id');
$table->primary(['post_id', 'tag_id']);  // Composite
```

### Foreign Keys

```php
// Basic foreign key
$table->foreignId('user_id')
    ->constrained()
    ->onDelete('cascade');

// Full syntax
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade')
    ->onUpdate('cascade');

// With custom name
$table->foreign('user_id', 'posts_user_fk')
    ->references('id')
    ->on('users');
```

### Foreign Key Actions

```php
->onDelete('cascade')      // Delete related records
->onDelete('set null')     // Set to NULL
->onDelete('restrict')     // Prevent deletion
->onDelete('no action')    // No action (default)

->onUpdate('cascade')      // Update related records
->onUpdate('set null')     // Set to NULL on update
```

### Dropping Indexes

```php
$table->dropIndex('users_email_index');
$table->dropUnique('users_email_unique');
$table->dropForeign('posts_user_id_foreign');
$table->dropPrimary();
```

---

## Best Practices

### 1. One Change Per Migration

```php
// GOOD: Single purpose
class AddAvatarToUsers extends Migration
{
    public function up(): void
    {
        $this->table('users', function ($table) {
            $table->string('avatar')->nullable();
        });
    }
}

// BAD: Multiple unrelated changes
class UpdateUserAndPosts extends Migration
{
    public function up(): void
    {
        // Don't mix unrelated changes
    }
}
```

### 2. Always Write Down Method

```php
public function up(): void
{
    $this->create('products', function ($table) {
        $table->id();
        $table->string('name');
    });
}

// Always reversible!
public function down(): void
{
    $this->drop('products');
}
```

### 3. Use Appropriate Column Sizes

```php
// GOOD: Appropriate sizes
$table->string('name', 100);        // Names rarely exceed 100 chars
$table->string('email', 255);       // Email max is 254 chars
$table->string('country_code', 2);  // ISO codes are 2 chars

// BAD: Wasteful
$table->string('country_code', 255); // Unnecessary
```

### 4. Index Foreign Keys

```php
$table->unsignedBigInteger('user_id');
$table->foreign('user_id')->references('id')->on('users');
$table->index('user_id');  // Important for JOIN performance
```

### 5. Use Timestamps

```php
// Always track creation/modification
$table->timestamps();  // Adds created_at and updated_at
```

### 6. Soft Deletes (Optional)

```php
$table->timestamp('deleted_at')->nullable();
// Or
$table->softDeletes();
```

---

## Complete Example

### Users Table Migration

```php
<?php
namespace App\Database\Migrations;

use App\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->create('users', function ($table) {
            // Primary key
            $table->id();
            
            // User info
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            
            // Profile
            $table->string('avatar', 255)->nullable();
            $table->text('bio')->nullable();
            
            // Status & role
            $table->enum('role', ['user', 'admin', 'moderator'])->default('user');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            // Email verification
            $table->timestamp('email_verified_at')->nullable();
            
            // Remember token for "remember me"
            $table->string('remember_token', 100)->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('email');
            $table->index('status');
            $table->index('role');
        });
    }
    
    public function down(): void
    {
        $this->drop('users');
    }
}
```

### Posts Table with Relationships

```php
<?php
namespace App\Database\Migrations;

use App\Database\Migration;

class CreatePostsTable extends Migration
{
    public function up(): void
    {
        $this->create('posts', function ($table) {
            $table->id();
            
            // Foreign key to users
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // Post content
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->text('content');
            $table->string('excerpt', 500)->nullable();
            $table->string('featured_image', 255)->nullable();
            
            // Status & metadata
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('views')->unsigned()->default(0);
            $table->timestamp('published_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('slug');
            $table->index('status');
            $table->index('published_at');
            $table->fulltext(['title', 'content']);
        });
    }
    
    public function down(): void
    {
        $this->drop('posts');
    }
}
```

---

## Troubleshooting

### Foreign Key Errors

```sql
-- Disable checks temporarily
SET FOREIGN_KEY_CHECKS = 0;
-- Run migration
SET FOREIGN_KEY_CHECKS = 1;
```

### Table Already Exists

```php
public function up(): void
{
    if (!$this->hasTable('users')) {
        $this->create('users', function ($table) {
            // ...
        });
    }
}
```

### Column Already Exists

```php
public function up(): void
{
    if (!$this->hasColumn('users', 'avatar')) {
        $this->table('users', function ($table) {
            $table->string('avatar')->nullable();
        });
    }
}
```

---

## Quick Reference

```php
// Create table
$this->create('table', fn($t) => $t->id());

// Modify table
$this->table('table', fn($t) => $t->string('col'));

// Drop table
$this->drop('table');

// Common columns
$table->id();                    // Primary key
$table->string('col', 100);      // VARCHAR
$table->text('col');             // TEXT
$table->integer('col');          // INT
$table->boolean('col');          // TINYINT(1)
$table->timestamps();            // created_at, updated_at

// Modifiers
->nullable()
->default('value')
->unique()
->index()

// Foreign key
$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
```
