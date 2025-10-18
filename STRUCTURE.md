# 📁 VelocityPHP - PROJECT STRUCTURE DOCUMENTATION

## Complete Folder & File Organization

```
Native Php/
│
├── 📁 public/                          # Web-accessible root (DocumentRoot)
│   ├── 📄 index.php                    # Single entry point - bootstraps app
│   ├── 📄 .htaccess                    # Apache config (URL rewriting, security)
│   │
│   └── 📁 assets/                      # Static files (CSS, JS, images)
│       ├── 📁 css/
│       │   └── 📄 app.css              # Main stylesheet (SPA transitions, UI)
│       │
│       ├── 📁 js/
│       │   └── 📄 app.js               # Core AJAX router & SPA engine
│       │
│       └── 📁 images/                  # Static images
│
├── 📁 src/                             # Application source code (non-public)
│   │
│   ├── 📁 config/                      # Configuration files
│   │   ├── 📄 app.php                  # App settings (debug, env, timezone)
│   │   └── 📄 database.php             # Database connections (MySQL, PostgreSQL, SQLite)
│   │
│   ├── 📁 controllers/                 # MVC Controllers
│   │   ├── 📄 BaseController.php       # Abstract base with helpers (json, validate, sanitize)
│   │   ├── 📄 HomeController.php       # Handles / route
│   │   ├── 📄 UsersController.php      # CRUD operations for users
│   │   └── 📄 ApiController.php        # API endpoints for AJAX
│   │
│   ├── 📁 models/                      # MVC Models
│   │   ├── 📄 BaseModel.php            # Abstract base with PDO & CRUD methods
│   │   └── 📄 UserModel.php            # User-specific database operations
│   │
│   ├── 📁 views/                       # MVC Views (Next.js-inspired structure)
│   │   │
│   │   ├── 📁 layouts/                 # Page wrappers/templates
│   │   │   └── 📄 main.php             # Default layout (header, footer, scripts)
│   │   │
│   │   ├── 📁 components/              # Reusable UI components
│   │   │   ├── 📄 navbar.php           # Navigation bar (included in layout)
│   │   │   └── 📄 footer.php           # Footer (included in layout)
│   │   │
│   │   ├── 📁 pages/                   # File-based routing (like Next.js)
│   │   │   │
│   │   │   ├── 📁 index/               # Route: /
│   │   │   │   └── 📄 index.php        # Home page content
│   │   │   │
│   │   │   ├── 📁 dashboard/           # Route: /dashboard
│   │   │   │   └── 📄 index.php        # Dashboard page
│   │   │   │
│   │   │   ├── 📁 users/               # Route: /users
│   │   │   │   ├── 📄 index.php        # Users list page
│   │   │   │   │
│   │   │   │   └── 📁 [id]/            # Dynamic route: /users/1, /users/2, etc.
│   │   │   │       └── 📄 index.php    # Single user detail page
│   │   │   │
│   │   │   └── 📁 about/               # Route: /about
│   │   │       └── 📄 index.php        # About page
│   │   │
│   │   └── 📁 errors/                  # Error pages
│   │       ├── 📄 404.php              # Not found page
│   │       └── 📄 500.php              # Server error page
│   │
│   ├── 📁 utils/                       # Utility classes & helpers
│   │   ├── 📄 Autoloader.php           # PSR-4 autoloader
│   │   ├── 📄 Router.php               # File-based routing engine
│   │   ├── 📄 Request.php              # HTTP request helper
│   │   └── 📄 Response.php             # HTTP response helper
│   │
│   ├── 📁 middleware/                  # Request middleware (auth, CORS, etc.)
│   │   └── (Add your middleware here)
│   │
│   └── 📁 services/                    # Business logic services
│       └── (Add your services here)
│
├── 📁 database/                        # Database files
│   └── 📄 schema.sql                   # Sample database schema
│
├── 📁 logs/                            # Application logs
│   └── 📄 .gitignore                   # Ignore log files in git
│
├── 📄 .env.example                     # Environment variables template
├── 📄 .gitignore                       # Git ignore rules
├── 📄 composer.json                    # Composer dependencies & autoload
├── 📄 README.md                        # Full documentation
└── 📄 QUICKSTART.md                    # Quick start guide
```

---

## 📖 File Descriptions

### **Core Files**

| File | Purpose |
|------|---------|
| `public/index.php` | Application entry point, initializes framework, handles all requests |
| `public/.htaccess` | Apache configuration for URL rewriting and security headers |
| `src/utils/Router.php` | Discovers routes from file structure, handles dynamic segments |
| `src/utils/Autoloader.php` | Automatically loads classes based on namespaces |

### **Frontend Assets**

| File | Purpose |
|------|---------|
| `public/assets/js/app.js` | AJAX router, intercepts links/forms, manages SPA navigation |
| `public/assets/css/app.css` | Core styles, loading states, transitions, notifications |

### **MVC Components**

| Component | Location | Purpose |
|-----------|----------|---------|
| **Models** | `src/models/` | Database interactions, business logic |
| **Views** | `src/views/pages/` | HTML templates (file = route) |
| **Controllers** | `src/controllers/` | Handle requests, process data, return responses |

---

## 🎯 Routing Convention

### Static Routes
```
File: src/views/pages/blog/index.php
Route: /blog
```

### Dynamic Routes
```
File: src/views/pages/blog/[slug]/index.php
Route: /blog/my-post (slug = "my-post")
Route: /blog/another-post (slug = "another-post")
```

### Nested Routes
```
File: src/views/pages/admin/users/[id]/edit/index.php
Route: /admin/users/123/edit (id = "123")
```

---

## 🔄 Request Flow

```
1. User clicks link → app.js intercepts
2. AJAX request sent to server
3. public/index.php receives request
4. Router.php finds matching view/controller
5. Controller processes (if exists)
6. View renders HTML
7. JSON response sent back
8. app.js injects content into DOM
9. Browser history updated (no refresh!)
```

---

## 📝 Naming Conventions

### Files
- Views: `index.php` (always)
- Controllers: `{Name}Controller.php` (PascalCase)
- Models: `{Name}Model.php` (PascalCase)
- Services: `{Name}Service.php` (PascalCase)

### Namespaces
- Controllers: `App\Controllers`
- Models: `App\Models`
- Utils: `App\Utils`
- Services: `App\Services`

### Routes
- URLs: lowercase, hyphenated (`/user-profile`)
- Folders: lowercase (`src/views/pages/user-profile/`)

---

## 🚀 Adding New Features

### New Page
1. Create folder: `src/views/pages/{pagename}/`
2. Add file: `index.php`
3. Done! Route auto-created at `/{pagename}`

### New Controller
1. Create file: `src/controllers/{Name}Controller.php`
2. Extend `BaseController`
3. Add namespace: `App\Controllers`
4. Router auto-detects and uses it

### New Model
1. Create file: `src/models/{Name}Model.php`
2. Extend `BaseModel`
3. Define `$table` and `$fillable`
4. Use in controllers

---

## 🔐 Security Files

| File | Security Feature |
|------|------------------|
| `.htaccess` | CSRF headers, XSS protection, content security policy |
| `index.php` | CSRF token validation, session security |
| `BaseModel.php` | Prepared statements, SQL injection prevention |
| `BaseController.php` | Input sanitization, validation |

---

## 📦 Scalability Tips

- **Small apps:** Use default structure
- **Medium apps:** Add `src/modules/` for feature separation
- **Large apps:** Split into microservices, use API gateway

---

## 🎨 Customization Points

1. **Layouts:** `src/views/layouts/` - Add admin.php, landing.php, etc.
2. **Components:** `src/views/components/` - Add modals, cards, etc.
3. **Middleware:** `src/middleware/` - Add auth, logging, etc.
4. **Services:** `src/services/` - Add email, payment, etc.

---

**This structure ensures:**
✅ Clean code organization  
✅ Easy navigation  
✅ Scalable architecture  
✅ Zero-refresh SPA behavior  
✅ Production-ready security
