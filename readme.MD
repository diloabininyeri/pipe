
# Zeus\Pipe

A lightweight, flexible middleware pipeline system for PHP, inspired by Laravel's middleware architecture. `Zeus\Pipe` enables you to process data through a series of named middleware functions with features like conditional execution, exception handling, and flow control.

## Features

- **Named Middleware**: Register middleware with unique names for easy management.
- **Conditional Execution**: Use `only()` and `without()` to control which middleware runs.
- **Flow Control**: Stop middleware execution with `stop()`.
- **Exception Handling**: Catch and handle errors with `catch()`.
- **Middleware Monitoring**: Track middleware execution with `watch()`.
- **Pipeline Reset**: Clear all middleware with `flush()`.
- **Closure Support**: Convert the pipeline to a reusable closure with `getClosure()`.

---

## Installation

Install via Composer:

```bash
composer require zeus/pipe
```

Alternatively, include it manually:

```php
use Zeus\Pipe\Pipe;
```

---

## Usage

### Basic Example

Create a pipeline and add middleware to process data sequentially:

```php
use Zeus\Pipe\Pipe;

$pipe = new Pipe();

$pipe->next('trim', fn($data, $next, $stop) => $next(trim($data)));
$pipe->next('to_upper', fn($data, $next, $stop) => $next(strtoupper($data)));

$result = $pipe->run("  hello world  "); // Returns: "HELLO WORLD"
```

Each middleware receives the input `$data`, a `$next` callback to pass data to the next middleware, and a `$stop` callback to halt the pipeline.

---

### Conditional Execution

#### `only()`

Run only specific middleware:

```php
$pipe->only(['trim']);
$result = $pipe->run("  hello  "); // Runs only 'trim', returns: "hello"
```

#### `without()`

Skip specific middleware:

```php
$pipe->without(['to_upper']);
$result = $pipe->run("  hello  "); // Skips 'to_upper', returns: "hello"
```

---

### Monitoring with `watch()`

Track middleware execution by logging input and output:

```php
$pipe->watch(function ($name, $before, $after) {
    printf("| %-12s | %-15s | %-15s |\n", $name, $before, $after);
});

$result = $pipe->run("  hello  ");
```

**Example Output:**

```
| Middleware   | Before          | After           |
|--------------|-----------------|-----------------|
| trim         | "  hello "      | "hello"         |
| to_upper     | "hello"         | "HELLO"         |
```

---

### Exception Handling with `catch()`

Handle exceptions thrown during middleware execution:

```php
$pipe->next('error', fn($data, $next, $stop) => throw new Exception("Invalid data"));

$pipe->catch(function (Throwable $e) {
    return "Error: " . $e->getMessage();
});

$result = $pipe->run("test"); // Returns: "Error: Invalid data"
```

---

### Flow Control with `stop()`

Halt the pipeline early based on a condition:

```php
$pipe->next('check', function ($data, $next, $stop) {
    if ($data === 'STOP') {
        return $stop('Pipeline stopped.');
    }
    return $next($data);
});

$result = $pipe->run('STOP'); // Returns: "Pipeline stopped."
```

---

### Pipeline as Closure with `getClosure()`

Convert the pipeline to a reusable closure:

```php
$closure = $pipe->getClosure();
$result = $closure("  hello  "); // Returns: "HELLO"
```

---

### Reset Pipeline with `flush()`

Remove all registered middleware:

```php
$pipe->flush(); // Clears all middleware
```

---

## License

This project is licensed under the [MIT License](LICENSE).
```