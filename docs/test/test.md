# VelocityPhp Testing Guide

Complete guide to testing in VelocityPhp framework.

---

## Table of Contents

1. [Overview](#overview)
2. [Running Tests](#running-tests)
3. [Test Structure](#test-structure)
4. [Writing Tests](#writing-tests)
5. [Test Categories](#test-categories)
6. [HTTP Testing](#http-testing)
7. [Database Testing](#database-testing)
8. [Security Testing](#security-testing)
9. [Best Practices](#best-practices)

---

## Overview

VelocityPhp includes a comprehensive test suite that validates:

- PSR-4 autoloading and PSR-12 coding standards
- Database connections and queries
- Authentication flows
- CRUD operations
- Security layers (CSRF, XSS, SQL injection)
- File uploads
- Full integration scenarios

### Test Files

```
tests/
├── TestRunner.php       # Main test suite (138 tests)
└── HttpTestRunner.php   # HTTP endpoint tests
```

---

## Running Tests

### Quick Start

```bash
# Run all unit/integration tests
php tests/TestRunner.php

# Run HTTP tests (requires running server)
php start.php &
php tests/HttpTestRunner.php
```

### Expected Output

```
╔════════════════════════════════════════════════════════════════╗
║          VELOCITYPHP COMPREHENSIVE TEST SUITE                  ║
╚════════════════════════════════════════════════════════════════╝

=== PSR STANDARDS ===
[PASS] PSR-4 autoloading works
[PASS] Class names use StudlyCaps
...

╔════════════════════════════════════════════════════════════════╗
║                      TEST SUMMARY                              ║
╚════════════════════════════════════════════════════════════════╝

  Total Tests: 138
  Passed: 138
  Failed: 0
  Success Rate: 100%

╔════════════════════════════════════════════════════════════════╗
║              ALL TESTS PASSED SUCCESSFULLY!                   ║
╚════════════════════════════════════════════════════════════════╝
```

### Exit Codes

- `0` - All tests passed
- `1` - One or more tests failed

Use in CI/CD:
```bash
php tests/TestRunner.php && echo "Deploy!" || echo "Fix tests first!"
```

---

## Test Structure

### TestRunner Class

```php
class TestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $errors = [];
    
    public function run()
    {
        $this->testPsrStandards();
        $this->testDatabaseConnection();
        $this->testMigrations();
        $this->testSecurityLayers();
        $this->testAuthentication();
        $this->testCrudOperations();
        $this->testErrorHandling();
        $this->testFileUpload();
        $this->testFullIntegration();
        
        $this->printSummary();
    }
    
    private function assert($condition, $message)
    {
        if ($condition) {
            $this->passed++;
            Console::success($message);
        } else {
            $this->failed++;
            $this->errors[] = $message;
            Console::error($message);
        }
    }
}
```

### Console Helper

```php
class Console
{
    public static function success($msg) { echo "\033[32m[PASS] {$msg}\033[0m\n"; }
    public static function error($msg)   { echo "\033[31m[FAIL] {$msg}\033[0m\n"; }
    public static function info($msg)    { echo "\033[36m[INFO] {$msg}\033[0m\n"; }
    public static function warning($msg) { echo "\033[33m[WARN] {$msg}\033[0m\n"; }
    public static function header($msg)  { echo "\n\033[1;35m=== {$msg} ===\033[0m\n"; }
}
```

---

## Writing Tests

### Basic Test Method

```php
private function testMyFeature()
{
    Console::header("MY FEATURE");
    
    // Test 1: Basic assertion
    $this->assert(
        true === true,
        "Basic assertion works"
    );
    
    // Test 2: Class exists
    $this->assert(
        class_exists('App\\Utils\\MyClass'),
        "MyClass exists"
    );
    
    // Test 3: Method returns expected value
    $result = MyClass::doSomething();
    $this->assert(
        $result === 'expected',
        "doSomething returns expected value"
    );
    
    // Test 4: Exception handling
    try {
        MyClass::riskyOperation();
        $this->assert(true, "Risky operation succeeded");
    } catch (\Exception $e) {
        $this->assert(false, "Risky operation failed: " . $e->getMessage());
    }
}
```

### Assertion Patterns

```php
// Boolean checks
$this->assert($value === true, "Value is true");
$this->assert($value !== false, "Value is not false");

// Null checks
$this->assert($result !== null, "Result is not null");
$this->assert($optional === null, "Optional is null");

// String checks
$this->assert(strpos($string, 'needle') !== false, "String contains needle");
$this->assert(strlen($string) > 0, "String is not empty");

// Array checks
$this->assert(count($array) > 0, "Array is not empty");
$this->assert(isset($array['key']), "Array has key");
$this->assert(in_array('value', $array), "Array contains value");

// Numeric checks
$this->assert($count > 0, "Count is positive");
$this->assert($id === (int)$id, "ID is integer");

// Object checks
$this->assert($obj instanceof MyClass, "Object is MyClass instance");
$this->assert(method_exists($obj, 'myMethod'), "Object has myMethod");
```

---

## Test Categories

### 1. PSR Standards

Tests coding standards compliance:

```php
private function testPsrStandards()
{
    Console::header("PSR STANDARDS");
    
    // PSR-4 Autoloading
    $this->assert(
        class_exists('App\\Models\\UserModel'),
        "PSR-4 autoloading works"
    );
    
    // StudlyCaps class names
    $this->assert(
        preg_match('/^[A-Z][a-zA-Z0-9]*$/', 'UserModel'),
        "Class names use StudlyCaps"
    );
    
    // No global variables
    $content = file_get_contents(SRC_PATH . '/models/UserModel.php');
    $this->assert(
        strpos($content, 'global $') === false,
        "No global variables in class"
    );
}
```

### 2. Database Tests

Tests database connectivity and operations:

```php
private function testDatabaseConnection()
{
    Console::header("DATABASE CONNECTION");
    
    try {
        $config = require CONFIG_PATH . '/database.php';
        $dsn = sprintf('mysql:host=%s;dbname=%s',
            $config['connections']['mysql']['host'],
            $config['connections']['mysql']['database']
        );
        
        $pdo = new PDO($dsn, $config['...']['username'], $config['...']['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->assert(true, "Database connection successful");
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        $this->assert($stmt !== false, "Database query works");
        
    } catch (PDOException $e) {
        $this->assert(false, "Database connection failed: " . $e->getMessage());
    }
}
```

### 3. Authentication Tests

Tests auth flows:

```php
private function testAuthentication()
{
    Console::header("AUTHENTICATION");
    
    $userModel = new \App\Models\UserModel();
    $testEmail = 'test_' . time() . '@test.com';
    
    // Test registration
    $hashedPassword = \App\Utils\Auth::hashPassword('Test123!');
    $userId = $userModel->create([
        'name' => 'Test User',
        'email' => $testEmail,
        'password' => $hashedPassword,
        'role' => 'user',
        'status' => 'active'
    ]);
    
    $this->assert($userId > 0, "User registration works");
    
    // Test login
    $loginResult = \App\Utils\Auth::login($testEmail, 'Test123!');
    $this->assert($loginResult === true, "Login works");
    
    // Test session
    $this->assert(\App\Utils\Auth::check(), "Auth check returns true");
    
    // Test logout
    \App\Utils\Auth::logout();
    $this->assert(!\App\Utils\Auth::check(), "Logout works");
    
    // Cleanup
    $userModel->delete($userId);
}
```

### 4. CRUD Tests

Tests Create, Read, Update, Delete:

```php
private function testCrudOperations()
{
    Console::header("CRUD OPERATIONS");
    
    $model = new \App\Models\UserModel();
    
    // CREATE
    $id = $model->create([
        'name' => 'CRUD Test',
        'email' => 'crud_' . time() . '@test.com',
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'role' => 'user',
        'status' => 'active'
    ]);
    $this->assert($id > 0, "CREATE works");
    
    // READ
    $user = $model->find($id);
    $this->assert($user !== null, "READ works");
    $this->assert($user['name'] === 'CRUD Test', "READ returns correct data");
    
    // UPDATE
    $model->update($id, ['name' => 'Updated Name']);
    $updated = $model->find($id);
    $this->assert($updated['name'] === 'Updated Name', "UPDATE works");
    
    // DELETE
    $model->delete($id);
    $deleted = $model->find($id);
    $this->assert($deleted === false || $deleted === null, "DELETE works");
}
```

### 5. Security Tests

Tests security layers:

```php
private function testSecurityLayers()
{
    Console::header("SECURITY LAYERS");
    
    // CSRF Token
    $token = \App\Utils\Security::generateCsrfToken();
    $this->assert(strlen($token) >= 32, "CSRF token generated");
    
    // XSS Protection
    $malicious = '<script>alert("xss")</script>';
    $clean = \App\Utils\Security::sanitizeOutput($malicious);
    $this->assert(
        strpos($clean, '<script>') === false,
        "XSS protection removes scripts"
    );
    
    // SQL Injection Detection
    $sqli = "1' OR '1'='1";
    $detected = \App\Utils\Security::detectSqlInjection($sqli);
    $this->assert($detected === true, "SQL injection detected");
    
    // Password Hashing
    $password = 'SecurePass123!';
    $hash = \App\Utils\Auth::hashPassword($password);
    $verified = \App\Utils\Auth::verifyPassword($password, $hash);
    $this->assert($verified === true, "Password hashing works");
}
```

### 6. File Upload Tests

Tests file upload validation:

```php
private function testFileUpload()
{
    Console::header("FILE UPLOAD");
    
    $uploader = new \App\Utils\FileUpload();
    
    // Test configuration
    $this->assert(
        method_exists($uploader, 'setMaxSize'),
        "FileUpload has setMaxSize method"
    );
    
    // Test invalid file rejection
    $invalid = ['name' => ''];
    $result = $uploader->upload($invalid);
    $this->assert($result === false, "Rejects invalid file");
    
    // Test size validation
    $uploader->setMaxSize(100); // 100 bytes
    $large = [
        'name' => 'test.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => '/tmp/test.jpg',
        'error' => UPLOAD_ERR_OK,
        'size' => 1000000
    ];
    $result = $uploader->upload($large);
    $this->assert($result === false, "Rejects oversized files");
}
```

---

## HTTP Testing

### HttpTestRunner

Tests actual HTTP endpoints:

```php
class HttpTestRunner
{
    private $baseUrl = 'http://localhost:8001';
    private $sessionCookie = null;
    
    public function testPageRoutes()
    {
        // Test home page
        $response = $this->get('/');
        $this->assert($response['code'] === 200, "Home page returns 200");
        
        // Test login page
        $response = $this->get('/login');
        $this->assert($response['code'] === 200, "Login page returns 200");
        
        // Test 404
        $response = $this->get('/nonexistent');
        $this->assert($response['code'] === 404, "404 for unknown route");
    }
    
    public function testApiAuth()
    {
        // Register
        $response = $this->post('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!'
        ]);
        $this->assert($response['code'] === 200, "Registration works");
        
        // Login
        $response = $this->post('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!'
        ]);
        $this->assert($response['code'] === 200, "Login works");
    }
    
    private function get($uri)
    {
        return $this->request('GET', $uri);
    }
    
    private function post($uri, $data = [])
    {
        return $this->request('POST', $uri, $data);
    }
    
    private function request($method, $uri, $data = [])
    {
        $ch = curl_init($this->baseUrl . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        if ($this->sessionCookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->sessionCookie);
        }
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body];
    }
}
```

### Running HTTP Tests

```bash
# Terminal 1: Start server
php start.php

# Terminal 2: Run HTTP tests
php tests/HttpTestRunner.php
```

---

## Database Testing

### Test Database Setup

Create a separate test database:

```sql
CREATE DATABASE native_test;
GRANT ALL ON native_test.* TO 'root'@'localhost';
```

### Using Test Database

```php
// In TestRunner constructor
public function __construct()
{
    // Use test database
    putenv('DB_DATABASE=native_test');
    
    // Load config after setting env
    $this->loadEnv();
}
```

### Transaction Rollback

```php
private function testWithRollback()
{
    $this->pdo->beginTransaction();
    
    try {
        // Run tests that modify data
        $this->runDataModifyingTests();
    } finally {
        // Always rollback to keep DB clean
        $this->pdo->rollBack();
    }
}
```

### Cleanup After Tests

```php
public function cleanup()
{
    if ($this->pdo) {
        // Delete test data
        $this->pdo->exec("DELETE FROM users WHERE email LIKE '%@test.com'");
        $this->pdo->exec("DELETE FROM users WHERE email LIKE '%@velocityphp.test'");
    }
}
```

---

## Security Testing

### CSRF Testing

```php
// Test CSRF token validation
$token = \App\Utils\Security::generateCsrfToken();
$_SESSION['csrf_token'] = $token;

$this->assert(
    \App\Utils\Security::validateCsrfToken($token),
    "Valid CSRF token accepted"
);

$this->assert(
    !\App\Utils\Security::validateCsrfToken('invalid'),
    "Invalid CSRF token rejected"
);
```

### XSS Testing

```php
$testCases = [
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '<svg onload=alert(1)>',
    'javascript:alert(1)',
    '<a href="javascript:alert(1)">click</a>'
];

foreach ($testCases as $input) {
    $output = \App\Utils\Security::sanitizeOutput($input);
    $this->assert(
        strpos($output, 'script') === false &&
        strpos($output, 'onerror') === false &&
        strpos($output, 'javascript:') === false,
        "XSS vector sanitized: " . substr($input, 0, 30)
    );
}
```

### SQL Injection Testing

```php
$sqliPatterns = [
    "' OR '1'='1",
    "1; DROP TABLE users--",
    "' UNION SELECT * FROM users--",
    "admin'--",
    "1' AND 1=1--"
];

foreach ($sqliPatterns as $pattern) {
    $detected = \App\Utils\Security::detectSqlInjection($pattern);
    $this->assert($detected, "SQLi detected: {$pattern}");
}
```

---

## Best Practices

### 1. Test Independence

Each test should be independent:

```php
// GOOD: Creates own test data
private function testUserCreation()
{
    $user = $this->createTestUser();
    // ... test ...
    $this->deleteTestUser($user['id']);
}

// BAD: Depends on external state
private function testUserCreation()
{
    $user = $model->find(1); // Assumes user 1 exists
}
```

### 2. Descriptive Messages

```php
// GOOD: Clear what's being tested
$this->assert($result, "User registration returns user ID");

// BAD: Vague
$this->assert($result, "Works");
```

### 3. Test Edge Cases

```php
private function testEdgeCases()
{
    // Empty input
    $result = $validator->validate([]);
    $this->assert($result === false, "Rejects empty input");
    
    // Null input
    $result = $validator->validate(null);
    $this->assert($result === false, "Handles null input");
    
    // Very long input
    $longString = str_repeat('a', 10000);
    $result = $validator->validate(['name' => $longString]);
    $this->assert($result === false, "Rejects oversized input");
}
```

### 4. Group Related Tests

```php
private function testUserModel()
{
    Console::header("USER MODEL");
    
    Console::subheader("Creation");
    // Creation tests...
    
    Console::subheader("Retrieval");
    // Retrieval tests...
    
    Console::subheader("Update");
    // Update tests...
    
    Console::subheader("Deletion");
    // Deletion tests...
}
```

### 5. Clean Up

```php
// Always clean up test data
private $testUserIds = [];

private function createTestUser()
{
    $id = $model->create([...]);
    $this->testUserIds[] = $id;
    return $id;
}

public function cleanup()
{
    foreach ($this->testUserIds as $id) {
        $model->delete($id);
    }
}
```

---

## Quick Reference

```bash
# Run all tests
php tests/TestRunner.php

# Run with verbose errors
php -d display_errors=1 tests/TestRunner.php

# Run HTTP tests
php tests/HttpTestRunner.php

# Check test syntax
php -l tests/TestRunner.php
```

### Adding New Test

1. Add method to TestRunner:
```php
private function testMyFeature()
{
    Console::header("MY FEATURE");
    $this->assert(true, "My test");
}
```

2. Call in `run()`:
```php
public function run()
{
    // ... existing tests ...
    $this->testMyFeature();
}
```

3. Run tests:
```bash
php tests/TestRunner.php
```
