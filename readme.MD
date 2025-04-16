## Pipe 
### A lightweight middleware pipeline for PHP
```markdown
# Zeus\Pipe

A flexible, lightweight middleware pipeline system for PHP. Inspired by Laravel-style middleware, `Zeus\Pipe` allows you to process data through a series of named middleware functions with conditional execution, exception handling, and flow control.

## Features

- ✅ Named middleware registration  
- 🔁 Conditional execution with `only()` and `without()`  
- 🛑 Flow control with `stop()`  
- 🧩 Exception handling via `catch()`  
- 👀 Middleware monitoring via `watch()`  
- 🧼 Easily flush all middleware with `flush()`  
- 🔄 Retrieve pipeline as a closure with `getClosure()`

---

## Installation

No package manager needed. Just include the class in your project:

```
```php
use Zeus\Pipe\Pipe;


---

## Basic Usage

```php
$pipe = new Pipe();

$pipe->next('trim', function ($data, $next, $stop) {
    return $next(trim($data));
});

$pipe->next('to_upper', function ($data, $next, $stop) {
    return $next(strtoupper($data));
});

$result = $pipe->run("  hello world  "); // Result: "HELLO WORLD"
```

---

## Conditional Middleware: `only()`

Run only specific middleware by name:

```php
$pipe->only(['trim']); // Only runs middleware named 'trim'
```

---

## Conditional Middleware: `without()`

Skip specific middleware by name:

```php
$pipe->without(['trim']); // Runs all except 'trim'
```

---

## Monitoring with `watch()`

Track each middleware’s input and output:

```php
$pipe->watch(function ($name, $before, $after) {
    printf("| %-12s | %-15s | %-15s |\n", $name, $before, $after);
});
```

### Example Output

| Middleware   | Before         | After          |
|--------------|----------------|----------------|
| trim         | "  hello "     | "hello"        |
| to_upper     | "hello"        | "HELLO"        |

---

## Exception Handling

Catch errors during pipeline execution:

```php
$pipe->catch(function (Throwable $e) {
    return "Error: " . $e->getMessage();
});
```

---

## Stopping the Pipeline

Stop execution based on a condition:

```php
$pipe->next('stopper', function ($data, $next, $stop) {
    if ($data === 'STOP') {
        return $stop('Pipeline stopped!');
    }
    return $next($data);
});
```

---

## Get as Closure

```php
$closure = $pipe->getClosure();
$result = $closure("example input");
```

---

## Flush All Middleware

```php
$pipe->flush();
```

---

## License

MIT License
```

Let me know if you want to generate a logo, badges, or a `composer.json` for publishing!
