# VelocityPHP — Models

## BaseModel

`src/models/BaseModel.php` — abstract base class for all models. Provides PDO-backed CRUD, query caching, pagination, and transactions.

---

## Creating a Model

```php
// src/models/PostModel.php
namespace App\Models;

class PostModel extends BaseModel
{
    protected $table      = 'posts';
    protected $primaryKey = 'id';        // default
    protected $timestamps = true;        // auto-manages created_at / updated_at
    protected $fillable   = [
        'title', 'content', 'user_id', 'status'
    ];
}
```

`$fillable` restricts which columns `create()` and `update()` accept. Leave empty to allow all columns.

---

## API Reference

### Find by primary key

```php
$post = $model->find(42);
// Returns assoc array or false
```

### Get all records

```php
$posts = $model->all();                        // up to 1 000 rows (safe default)
$posts = $model->all('created_at DESC', 50);   // ordered, explicit limit
```

> The 1 000-row default prevents accidental full-table scans. Always pass an explicit `$limit` for large tables.

### Where clause

```php
// Equality conditions
$posts = $model->where(
    ['status' => 'published', 'user_id' => 5],
    'created_at DESC',   // ORDER BY (optional)
    20                   // LIMIT (optional)
);

// Operators
$posts = $model->whereAdvanced([
    'views'      => ['>', 100],
    'status'     => 'published',
], 'views DESC', 10);
```

### Create

```php
$id = $model->create([
    'title'   => 'Hello World',
    'content' => 'Body text',
    'user_id' => 1,
    'status'  => 'draft',
]);
// Returns last insert ID
```

### Update

```php
$model->update(42, ['status' => 'published']);
```

### Delete

```php
$model->delete(42);
```

### Count

```php
$total = $model->count();
$published = $model->count(['status' => 'published']);
```

### Paginate

```php
$result = $model->paginate(
    $page    = 1,
    $perPage = 15,
    $conditions = ['status' => 'published'],
    $orderBy    = 'created_at DESC'
);

// $result keys:
// data         — array of rows
// total        — total matching rows
// per_page     — rows per page (max 100)
// current_page — current page number
// last_page    — total pages
// from / to    — row range
```

### Batch Insert

```php
$model->batchInsert([
    ['title' => 'Post 1', 'content' => '...', 'user_id' => 1, 'status' => 'draft'],
    ['title' => 'Post 2', 'content' => '...', 'user_id' => 1, 'status' => 'draft'],
]);
// Returns row count
```

### Raw queries

```php
// SELECT — returns array of rows
$rows = $model->query("SELECT * FROM posts WHERE views > ?", [100]);

// INSERT / UPDATE / DELETE — returns bool
$model->execute("UPDATE posts SET views = views + 1 WHERE id = ?", [42]);

// Cached SELECT (TTL in seconds)
$rows = $model->cachedQuery("SELECT COUNT(*) as n FROM posts", [], 300);
```

### Transactions

```php
$model->beginTransaction();
try {
    $model->create([...]);
    $model->update(5, [...]);
    $model->commit();
} catch (\Exception $e) {
    $model->rollback();
    throw $e;
}
```

### Performance stats

```php
$stats = BaseModel::getStats();
// ['queries' => 42, 'cache_hits' => 17, 'active_connections' => 1, 'cache_size' => 5]
```

---

## UserModel

`src/models/UserModel.php`

| Method | Returns | Description |
|--------|---------|-------------|
| `findByEmail($email)` | array\|false | Find user by email |
| `createUser($data)` | int | Create user, returns ID |
| `updatePassword($id, $hash)` | bool | Update password hash |
| `getAllUsers($limit)` | array | List users |

```php
$user = new \App\Models\UserModel();
$found = $user->findByEmail('user@example.com');
```

---

## Identifier Safety

All column and table names are escaped with backticks (MySQL/SQLite) or double-quotes (PostgreSQL) by `escapeIdentifier()`. Never interpolate untrusted input directly into SQL — use prepared statement parameters (`?`) instead.

---

## Configuration

Database settings are in `src/config/database.php`. The active driver is set by the `default` key and must match one of the `connections` entries.

```php
// src/config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => getenv('DB_HOST') ?: 'localhost',
            'port'     => getenv('DB_PORT') ?: 3306,
            'database' => getenv('DB_NAME') ?: 'velocity',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset'  => 'utf8mb4',
        ],
    ],
];
```
