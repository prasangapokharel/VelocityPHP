# 🐛 DEBUGGING GUIDE

## Advanced Error Logging & Debugging System

This framework includes a **highly powerful debugging system** with beautiful error pages, detailed logging, and fast debugging capabilities.

---

## ✨ Features

### 🎨 Beautiful Error Pages
- **Color-coded** stack traces
- **Code snippets** showing the error line
- **Request information** (method, URI, IP, time)
- **Memory usage** and PHP version
- **Production/Development** modes

### 📋 Advanced Logging
- **Multiple log levels** (ERROR, WARNING, INFO, DEBUG)
- **Automatic context** capture
- **Daily log files** with auto-cleanup
- **Request tracking** (IP, URI, method)
- **Exception logging** with full stack traces

### 🚀 Fast Debugging
- **Real-time log viewer** at `/logs`
- **Filter by level** (errors, warnings, info, debug)
- **Auto-refresh** capability
- **Search and filter** logs
- **Visual statistics** dashboard

---

## 📖 Usage Guide

### 1. Logging Errors

```php
use App\Utils\Logger;

// Error logging
Logger::error('Database connection failed', [
    'host' => 'localhost',
    'database' => 'mydb'
]);

// Warning logging
Logger::warning('Slow query detected', [
    'query' => 'SELECT * FROM users',
    'time' => '2.5s'
]);

// Info logging
Logger::info('User logged in', [
    'user_id' => 123,
    'ip' => '192.168.1.1'
]);

// Debug logging
Logger::debug('Processing payment', [
    'amount' => 99.99,
    'method' => 'credit_card'
]);

// Exception logging
try {
    // Your code
} catch (Exception $e) {
    Logger::exception($e);
}
```

### 2. Viewing Logs

#### Via Web Interface
Navigate to: **http://localhost:8000/logs**

Features:
- ✅ See all errors and exceptions
- ✅ Filter by log level
- ✅ Real-time statistics
- ✅ Auto-refresh every 10 seconds

#### Via Log Files
Logs are stored in `logs/` directory:

```
logs/
├── app-2025-10-18.log     # Daily application logs
├── error.log              # All errors and exceptions
└── .gitignore
```

### 3. Error Display Modes

#### Development Mode (Beautiful Error Pages)
When `debug = true` in `config/app.php`:

**Features:**
- 🎨 Beautiful dark-themed error page
- 📝 Code snippet with highlighted error line
- 📚 Full stack trace with file locations
- 🌐 Request information
- 💾 Memory usage
- 🔍 PHP version

**Example:**
```
╔═══════════════════════════════════════╗
║  ERROR: Call to undefined function   ║
║  File: UserController.php            ║
║  Line: 42                            ║
╚═══════════════════════════════════════╝

Code Snippet:
  40 | public function index()
  41 | {
> 42 |     $users = $this->fetchUsers();  ← ERROR
  43 |     return view('users', $users);
  44 | }

Stack Trace:
  #0 Router.php:125
  #1 index.php:76
```

#### Production Mode (User-Friendly)
When `debug = false`:

**Features:**
- ✅ Clean, simple error message
- ✅ No technical details exposed
- ✅ "Go Home" button
- ✅ Error logged in background

---

## 🔧 Configuration

### Enable Debug Mode

Edit `src/config/app.php`:

```php
'debug' => true,  // Show detailed errors
'log_level' => 'debug'  // Log everything
```

### Disable Debug Mode (Production)

```php
'debug' => false,  // Hide error details
'log_level' => 'error'  // Only log errors
```

### Log Levels

```php
'log_level' => 'debug'    // Log everything
'log_level' => 'info'     // Log info, warnings, errors
'log_level' => 'warning'  // Log warnings and errors
'log_level' => 'error'    // Log only errors
```

---

## 📊 Log Viewer Features

### Statistics Dashboard
```
┌─────────────────────────────────────┐
│ 📝 Total Logs Today:      247       │
│ ❌ Errors Today:           3        │
│ ✅ Status:                Good      │
│ 🕐 Last Update:           14:32:05  │
└─────────────────────────────────────┘
```

### Filter Options
- **All** - Show all log entries
- **Errors** - Show only errors
- **Warnings** - Show only warnings
- **Info** - Show info messages
- **Debug** - Show debug messages

### Auto-Refresh
The log viewer automatically refreshes every 10 seconds to show the latest logs.

---

## 🛠️ Advanced Usage

### Custom Error Handler

Create custom error handler in your controller:

```php
try {
    // Your code
} catch (Exception $e) {
    // Log the exception
    Logger::exception($e);
    
    // Return custom error response
    return $this->jsonError('Operation failed', [], 500);
}
```

### AJAX Error Handling

Errors in AJAX requests return JSON:

```json
{
  "success": false,
  "error": "Database connection failed",
  "type": "PDOException",
  "file": "/path/to/file.php",
  "line": 42,
  "trace": [
    "#0 UserModel.php:25",
    "#1 UserController.php:42"
  ]
}
```

### Log Context

Add context to any log entry:

```php
Logger::error('Payment failed', [
    'user_id' => $userId,
    'amount' => $amount,
    'card_last4' => '1234',
    'error_code' => 'DECLINED',
    'timestamp' => time()
]);
```

Result in log file:
```
[2025-10-18 14:32:05] [ERROR] Payment failed
Context:
  user_id: 123
  amount: 99.99
  card_last4: 1234
  error_code: DECLINED
  timestamp: 1729267925
Request: POST /api/payment
IP: 192.168.1.100
--------------------------------------------------------------------------------
```

---

## 🎯 Debugging Workflow

### 1. Development Debugging

```php
// Add debug logs throughout your code
Logger::debug('Start processing order', ['order_id' => $orderId]);

// ... your code ...

Logger::debug('Order validated', ['total' => $total]);

// ... more code ...

Logger::debug('Order complete', ['payment_id' => $paymentId]);
```

### 2. Check Logs Page
1. Navigate to `/logs`
2. Click "Debug" filter
3. See your debug trail

### 3. Production Monitoring
1. Set `debug = false`
2. Set `log_level = 'error'`
3. Monitor `/logs` for errors
4. Review `logs/error.log` file

---

## 🚨 Common Debugging Scenarios

### Scenario 1: Route Not Working
```php
// Add to Router.php
Logger::debug('Route found', [
    'uri' => $uri,
    'route' => $route,
    'params' => $params
]);
```

### Scenario 2: Database Query Failed
```php
// Add to Model
try {
    $result = $this->query($sql, $params);
    Logger::info('Query executed', ['rows' => count($result)]);
} catch (Exception $e) {
    Logger::error('Query failed', [
        'sql' => $sql,
        'params' => $params,
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

### Scenario 3: AJAX Request Issues
```php
// Add to Controller
Logger::debug('AJAX request received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'data' => $_POST,
    'is_ajax' => $isAjax
]);
```

---

## 📈 Performance Monitoring

### Log Execution Time
```php
$start = microtime(true);

// Your code here

$time = microtime(true) - $start;
Logger::info('Operation completed', [
    'duration' => round($time, 4) . 's'
]);
```

### Log Memory Usage
```php
Logger::debug('Memory check', [
    'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
    'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
]);
```

---

## 🔍 Error Page Components

### Code Snippet Section
Shows 10 lines before and after the error with:
- Line numbers
- Highlighted error line (red background)
- Full code context

### Stack Trace Section
Shows complete call stack with:
- Function/method names
- File paths
- Line numbers
- Click to expand details

### Request Information Section
Shows:
- HTTP method (GET, POST, etc.)
- Request URI
- IP address
- Timestamp
- PHP version
- Memory usage

---

## 🧹 Log Maintenance

### Auto-Cleanup
Old log files (30+ days) are automatically cleaned:

```php
// Run manually if needed
Logger::cleanOldLogs(30); // Keep 30 days
```

### Manual Cleanup
Delete log files manually:

```bash
# Delete all logs
rm logs/*.log

# Delete old logs only
find logs/ -name "app-*.log" -mtime +30 -delete
```

---

## 💡 Pro Tips

### 1. Structured Logging
Always include context:
```php
✅ Logger::error('Login failed', ['username' => $user, 'ip' => $ip]);
❌ Logger::error('Login failed');
```

### 2. Log Levels
Use appropriate levels:
```php
ERROR   - Critical failures (500 errors, exceptions)
WARNING - Issues that don't break functionality
INFO    - Important business events (user login, purchase)
DEBUG   - Development debugging information
```

### 3. Production Logging
In production:
- ✅ Log errors and warnings
- ✅ Log important business events
- ❌ Don't log debug information
- ❌ Don't log sensitive data (passwords, tokens)

### 4. Performance
- Log files are fast (file write)
- Logs don't slow down application
- Clean old logs regularly
- Monitor log file sizes

---

## 🎉 Quick Reference

### Log Something
```php
Logger::error('Message', ['key' => 'value']);
Logger::warning('Message');
Logger::info('Message');
Logger::debug('Message');
Logger::exception($exception);
```

### View Logs
```
Web:  http://localhost:8000/logs
File: logs/app-YYYY-MM-DD.log
File: logs/error.log
```

### Toggle Debug Mode
```php
// src/config/app.php
'debug' => true   // Development
'debug' => false  // Production
```

---

## 🚀 You're Ready!

Your application now has:
- ✅ Beautiful error pages
- ✅ Comprehensive logging
- ✅ Real-time log viewer
- ✅ Fast debugging capabilities
- ✅ Production-ready error handling

**Happy Debugging! 🐛🔧**
