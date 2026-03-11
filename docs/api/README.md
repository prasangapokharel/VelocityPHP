# VelocityPHP — REST API

All API endpoints are prefixed with `/api/`. Responses are JSON.

---

## Authentication

API endpoints that require authentication check `Auth::check()` (session or remember-me cookie). There is no separate API token system — browser sessions are used.

---

## CSRF

All mutating requests (POST, PUT, DELETE) require the CSRF token:

```
X-CSRF-Token: <value>
```

Get the token from the `<meta name="csrf-token">` tag in the HTML layout, or from:

```
GET /api/csrf-token
→ {"token": "abc123..."}
```

The bundled JavaScript engine (`public/assets/js/app.js`) reads the meta tag automatically and includes the header on every AJAX request.

---

## Response Format

### Success

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { ... }
}
```

### Error

```json
{
  "success": false,
  "message": "Human-readable message",
  "errors": { "field": "Validation message" }
}
```

HTTP status codes follow REST conventions: `200`, `201`, `400`, `401`, `403`, `404`, `422`, `500`.

---

## Endpoints

### Users

#### `GET /api/users`

List users (paginated).

**Query parameters:**

| Param | Default | Description |
|-------|---------|-------------|
| `page` | `1` | Page number |
| `per_page` | `15` | Rows per page (max 100) |

**Response:**
```json
{
  "success": true,
  "message": "Users retrieved",
  "data": {
    "data": [ { "id": 1, "name": "Alice", "email": "alice@example.com", ... } ],
    "total": 42,
    "per_page": 15,
    "current_page": 1,
    "last_page": 3,
    "from": 1,
    "to": 15
  }
}
```

---

#### `POST /api/users`

Create a new user.

**Headers:** `X-CSRF-Token: <token>`  
**Body (JSON or form-encoded):**

```json
{
  "name": "Alice",
  "email": "alice@example.com",
  "password": "secret1234",
  "password_confirmation": "secret1234"
}
```

**Response `201`:**
```json
{ "success": true, "message": "User created", "data": { "id": 5 } }
```

---

#### `GET /api/users/{id}`

Get a single user by ID.

**Response `200`:**
```json
{ "success": true, "message": "User found", "data": { "id": 5, "name": "Alice", ... } }
```

**Response `404`:**
```json
{ "success": false, "message": "User not found", "errors": [] }
```

---

#### `PUT /api/users/{id}`

Update a user.

**Headers:** `X-CSRF-Token: <token>`  
**Body:**

```json
{ "name": "Alice Updated", "email": "new@example.com" }
```

**Response `200`:**
```json
{ "success": true, "message": "User updated", "data": {} }
```

---

#### `DELETE /api/users/{id}`

Delete a user.

**Headers:** `X-CSRF-Token: <token>`

**Response `200`:**
```json
{ "success": true, "message": "User deleted", "data": {} }
```

---

### File Upload

#### `POST /api/upload`

Upload a file.

**Headers:** `X-CSRF-Token: <token>`  
**Body:** `multipart/form-data` with field `file`.

**Allowed types:** `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `application/pdf`  
**Max size:** 10 MB

**Response `200`:**
```json
{
  "success": true,
  "message": "File uploaded",
  "data": {
    "filename": "abc123def456.jpg",
    "path": "/storage/uploads/abc123def456.jpg",
    "size": 204800,
    "mime": "image/jpeg"
  }
}
```

---

## JavaScript Usage

```javascript
// Using the built-in Velocity.ajax helper (app.js)
Velocity.ajax('POST', '/api/users', {
    name: 'Alice',
    email: 'alice@example.com',
    password: 'secret1234',
    password_confirmation: 'secret1234'
}).then(res => {
    console.log(res.data.id);
});

// Or plain fetch (token from meta tag)
const token = document.querySelector('meta[name="csrf-token"]').content;
fetch('/api/users/5', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
    },
    body: JSON.stringify({ name: 'New Name' }),
});
```

---

## Adding a New API Endpoint

1. Add the route in `src/routes/web.php`:

   ```php
   RouteCollection::get('/api/posts', 'ApiController@getPosts');
   RouteCollection::post('/api/posts', 'ApiController@createPost');
   ```

2. Implement the method in `src/controllers/ApiController.php`:

   ```php
   public function getPosts($params, $isAjax)
   {
       Auth::require();
       $page  = (int)($this->get('page') ?? 1);
       $posts = (new PostModel())->paginate($page, 15);
       return $this->jsonSuccess('Posts retrieved', $posts);
   }
   ```
