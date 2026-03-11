# VelocityPHP — Controllers

## BaseController

`src/controllers/BaseController.php` — all controllers must extend this.

---

## Creating a Controller

```php
// src/controllers/PostController.php
namespace App\Controllers;

class PostController extends BaseController
{
    public function index()
    {
        $posts = (new \App\Models\PostModel())->paginate(1, 15);
        return $this->view('posts/index', ['posts' => $posts], 'Posts');
    }

    public function show($params, $isAjax)
    {
        $post = (new \App\Models\PostModel())->find($params['id']);
        if (!$post) {
            return $this->notFound();
        }
        return $this->view('posts/show', ['post' => $post], $post['title']);
    }

    public function store($params, $isAjax)
    {
        \App\Utils\Auth::require(); // must be logged in

        $data   = $this->post();
        $errors = $this->validate($data, [
            'title'   => 'required|min:3|max:255',
            'content' => 'required',
        ]);
        if ($errors !== true) {
            return $this->jsonError('Validation failed', $errors, 400);
        }

        $id = (new \App\Models\PostModel())->create([
            'title'   => $data['title'],
            'content' => $data['content'],
            'user_id' => \App\Utils\Auth::id(),
        ]);

        return $this->jsonSuccess('Post created', ['id' => $id], 201);
    }
}
```

Register the route in `src/routes/web.php`:

```php
RouteCollection::get('/posts', 'PostController@index');
RouteCollection::get('/posts/{id}', 'PostController@show');
RouteCollection::post('/posts', 'PostController@store');
```

---

## API Reference

### Reading Request Data

```php
// All POST / JSON body fields as assoc array
$data = $this->post();

// Single field (works for POST, PUT, PATCH, DELETE — parses php://input)
$email = $this->post('email');

// Query string
$page = $this->get('page', 1);    // default = 1

// Route parameter (passed as $params argument)
$id = $params['id'];
```

### Returning Responses

```php
// Render a view through the layout
return $this->view(
    'posts/index',              // src/views/pages/posts/index.php
    ['posts' => $posts],        // variables available in view
    'Post List'                 // <title> / $title
);

// JSON success
return $this->jsonSuccess('Created', ['id' => 5], 201);
// → {"success":true,"message":"Created","data":{"id":5}}

// JSON error
return $this->jsonError('Not found', [], 404);
// → {"success":false,"message":"Not found","errors":[]}

// Redirect
\App\Utils\Response::redirect('/dashboard');
```

### Validation

```php
$errors = $this->validate($data, [
    'name'     => 'required|min:3|max:100',
    'email'    => 'required|email',
    'password' => 'required|min:8',
    'confirm'  => 'required|confirmed',   // must match 'password'
    'age'      => 'numeric',
    'bio'      => 'max:1000',
]);

if ($errors !== true) {
    return $this->jsonError('Validation failed', $errors, 400);
}
```

Available rules:

| Rule | Description |
|------|-------------|
| `required` | Field must be present and non-empty |
| `email` | Valid email address |
| `min:N` | Minimum string length N |
| `max:N` | Maximum string length N |
| `numeric` | Must be numeric |
| `confirmed` | Must match `{field}_confirmation` |

### CSRF

CSRF protection is automatic for all POST/PUT/DELETE routes handled through the router. The token is stored in `$_SESSION['csrf_token']` and sent as `X-CSRF-Token` by the JavaScript engine.

For API calls from external clients, include the header:
```
X-CSRF-Token: <value from meta tag>
```

---

## Method Signatures

Route handler methods can take 0, 1, or 2 arguments:

```php
// No parameters (GET route with no placeholders)
public function index() { }

// Route parameters only
public function show($params) {
    $id = $params['id'];
}

// Route parameters + AJAX detection
public function update($params, $isAjax) {
    if ($isAjax) {
        return $this->jsonSuccess('Updated');
    }
    \App\Utils\Response::redirect('/users/' . $params['id']);
}
```

---

## Authentication in Controllers

```php
// Redirect to /login if not authenticated
\App\Utils\Auth::require();

// Redirect to / if authenticated but wrong role
\App\Utils\Auth::requireRole('admin');

// Check without redirecting
if (\App\Utils\Auth::check()) { ... }

// Get current user
$user = \App\Utils\Auth::user();
```

---

## Built-in Controllers

| Controller | File | Routes |
|-----------|------|--------|
| `HomeController` | `src/controllers/HomeController.php` | `GET /`, `GET /about` |
| `AuthController` | `src/controllers/AuthController.php` | `GET/POST /login`, `GET/POST /register`, `GET /logout` |
| `DashboardController` | `src/controllers/DashboardController.php` | `GET /dashboard` |
| `UsersController` | `src/controllers/UsersController.php` | `GET /users` |
| `ApiController` | `src/controllers/ApiController.php` | `/api/users/*`, `POST /api/upload` |
| `CryptoController` | `src/controllers/CryptoController.php` | `GET /crypto` |
