
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
# Zeus\Pipe\Filter

A simple, chainable filter class using closures.

## 🚀 Example

```php
use Zeus\Pipe\Filter;

$filter = new Filter();

$filter
    ->add(fn($value) => is_int($value))      // Must be an integer
    ->add(fn($value) => $value > 10)        // Greater than 10
    ->add(fn($value) => $value % 2 === 0)
    ->orAdd(fn($value)=>true);//or operator
       
$data = [5, 12, 15, 20, '30', 25];

$result = $filter->apply($data);
//or
$closure=$filter->getClosure();
array_filter($array,$closure);
//

print_r($result);
// Output: [1 => 12, 3 => 20]
```
**or Using a named filter**
```php
$filter = new Filter();


$filter->defineFilter('even_bigger_than_5', function (Filter $filter) {
    $filter
        ->add(static fn($value) => $value % 2 === 0)
        ->add(static fn($value) => $value > 5);
});

$filter->defineFilter('odd_and_not_negative', function (Filter $filter) {
    $filter
        ->add(static fn($value) => $value % 2 !== 0)
        ->add(static fn($value) => $value >= 0);
});


$filter->applyTo('even_bigger_than_5', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

$filtered=$filter->multipleApplyTo(
    [
        'even_bigger_than_5',
        'odd_and_not_negative'
    ],
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    true
);

print_r($filtered);

print_r($filter->getRejected());
```
**You can extend defineFilter so you can create more dynamic filters.**
```php


$filter = new Filter();

$filter->defineMultipleFilter([
    'isEven' => static function (Filter $filter) {
        $filter
            ->add(fn(int $value)=>$value % 2 === 0);
    },

]);

$filter->extendDefinedFilter('isEven', static function (Filter $filter) {
    $filter
        ->add(fn(int $value)=>$value>5);
});

$filter->extendDefinedFilter('isEven', static function (Filter $filter) {
    $filter
        ->orAdd(fn(int $value)=>$value<10);
});


$applyTo = $filter->applyTo('isEven', range(1,25));
print_r($applyTo);

```
**! operator in named**
<br>
To do the opposite of a filter, just use !, for example, we use `adult` filter for adult and `!adult` for non-adult.
```php
$filter = new Filter();


$filter->defineFilter('even_bigger_than_5', function (Filter $filter) {
    $filter
        ->add(static fn($value) => $value > 5);
});

$filter->defineFilter('odd_and_not_negative', function (Filter $filter) {
    $filter
        ->add(static fn($value) => $value % 2 !== 0)
        ->add(static fn($value) => $value >= 0);
});


$filter->applyTo('!even_bigger_than_5', [1, 2, 3, 4, 5, 6, 7, 8, 9]); //not even_bigger_than_5
$filter->applyTo('even_bigger_than_5', [1, 2, 3, 4, 5, 6, 7, 8, 9]); //even_bigger_than_5


$filter->multipleApplyTo([
    '!even_bigger_than_5',
    'odd_and_not_negative',
],[
    1, 2, 3, 4, 5, 6, 7, 8, 9
]);

```
**set the not operator**

By default, its value is `!`
```php
$filter = new Filter();


$filter->notOperator('not_');


$filter->defineFilter('even_bigger_than_5', function (Filter $filter) {
    $filter
        ->add(static fn($value) => $value > 5);
});


$filter->applyTo('not_even_bigger_than_5', [1, 2, 3, 4, 5, 6, 7, 8, 9]); //not even_bigger_than_5



```

**short using of the `define` method** 

Attention! This method is experimental, may have side effects and has not yet been fully tested.
```php

$filter = new Filter();
$filter
   ->define('isEven_bigger_than_5')
   ->add(fn($value) => $value % 2 === 0)
   ->orAdd(fn($value) => $value % 2 !== 0)
   ->add(fn($value) => $value > 5);

$filter->applyTo('isEven_bigger_than_5', [1, 2, 3, 4, 5, 6, 7, 8, 9]); //even_bigger_than_5
```
# Zeus\Pipe\Map
A simple, chainable map class using closures.
## 🚀 Example

```php
use Zeus\Pipe\Map;
$map = new Map();
$map
    ->add(fn($value) => $value * 2)   // Multiply by 2
    ->add(fn($value) => $value + 1);  // Add 1
         
$data = [1, 2, 3, 4, 5];

$result = $map->apply($data);

print_r($result);
// Output: [2, 5, 8, 11, 14]

//or
$closure=$map->getClosure();
array_map($closure,$array);
```
---

## License

This project is licensed under the [MIT License](LICENSE).
```