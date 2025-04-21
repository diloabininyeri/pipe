<?php

namespace Zeus\Pipe;

use Closure;
use InvalidArgumentException;

/**
 *
 */
class Filter
{
    /**
     * @var Closure
     */
    private Closure $filter;

    /**
     * @var array
     */
    private array $rejected = [];

    /**
     * @var Closure|null
     */
    private ?Closure $ddClosure = null;

    /**
     * @var array
     */
    private array $definedFilters = [];

    /**
     * @var string $notOperator
     */
    private string $notOperator = '!';

    /**
     *
     */
    public function __construct()
    {
        $this->resetFilterClosure();
    }

    /**
     * @param Closure(mixed $value):bool $closure
     * @return $this
     */
    public function add(Closure $closure): self
    {
        $previous = $this->filter;
        $this->filter = static fn($value) => $previous($value) && $closure($value);
        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function orAdd(Closure $closure): self
    {
        $previous = $this->filter;
        $this->filter = static fn($value) => $previous($value) || $closure($value);
        return $this;
    }

    /***
     * @param float|int|Closure $threshold
     * @return $this
     */
    public function greaterThan(float|int|Closure $threshold): self
    {
        if ($threshold instanceof Closure) {
            return $this->add(fn($value) => $threshold() < $value);
        }
        return $this->add(fn($value) => is_numeric($value) && $value > $threshold);
    }

    /***
     * @param float|int|Closure $threshold
     * @return $this
     */
    public function lowerThan(float|int|Closure $threshold): self
    {
        if ($threshold instanceof Closure) {
            return $this->add(fn($value) => $threshold() > $value);
        }
        return $this->add(fn($value) => is_numeric($value) && $value < $threshold);
    }

    /**
     * @param string $substring
     * @return $this
     */
    public function contains(string $substring): self
    {
        return $this->add(fn($value) => is_string($value) && str_contains($value, $substring));
    }

    /***
     * @param array $array
     * @param bool $applyRejected
     * @return array
     */
    public function apply(array $array, bool $applyRejected = false): array
    {
        if ($applyRejected) {
            return $this->filterWithRejected($array);
        }

        $filtered = array_filter($array, $this->filter);
        if ($this->ddClosure) {
            call_user_func($this->ddClosure, $filtered);
        }
        return $filtered;
    }

    /**
     * @noinspection PhpUnused
     * @return Closure
     */
    public function getClosure(): Closure
    {
        return $this->filter;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function test(mixed $value): bool
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        return !empty($this->apply($value));
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function without(Closure $callback): self
    {
        return $this->add(function () use ($callback) {
            return !$callback(...func_get_args());
        });
    }

    /**
     * @param bool $condition
     * @param Closure $callback
     * @return $this
     */

    public function when(bool $condition, Closure $callback): self
    {
        !$condition ?: $this->add($callback);
        return $this;
    }

    /**
     * @param bool $condition
     * @param Closure $callback
     * @return $this
     */
    public function unless(bool $condition, Closure $callback): self
    {
        return $this->when(!$condition, $callback);
    }

    /**
     * @return array
     */
    public function getRejected(): array
    {
        return $this->rejected;
    }

    /***
     * @param array $array
     * @return array
     */
    private function filterWithRejected(array $array): array
    {
        $this->rejected = [];
        $filtered = [];
        foreach ($array as $key => $value) {
            $passes = ($this->filter)($value);
            if ($passes) {
                $filtered[$key] = $value;
            } else {
                $this->rejected[] = $value;
            }
        }
        return $filtered;
    }


    /**
     * @param int|Closure $min
     * @param int|Closure $max
     * @return $this
     */
    public function between(int|Closure $min, int|Closure $max): self
    {
        return $this->add(function ($value) use ($min, $max) {
            if (!is_numeric($value)) {
                return false;
            }

            $minCheck = $min instanceof Closure ? $min($value) : $value >= $min;
            $maxCheck = $max instanceof Closure ? $max($value) : $value <= $max;

            return $minCheck && $maxCheck;
        });
    }

    /**
     * @param Closure|null $debug
     * @return $this
     */
    public function dd(?Closure $debug): self
    {
        $this->ddClosure = static function ($value) use ($debug) {
            if (function_exists('dd')) {
                dd($value);
            }
            if ($debug) {
                $debug($value);
                exit;
            }
            print_r($value);
            exit();

        };
        return $this;
    }

    /***
     * @param string $name
     * @param callable $callback
     * @return $this
     */
    public function defineFilter(string $name, callable $callback): self
    {
        $this->definedFilters[$name] = fn() => $callback($this);
        return $this;
    }

    /**
     * @param string $filterName
     * @param array $array
     * @param bool $withRejected
     * @return array
     */
    public function applyTo(string $filterName, array $array, bool $withRejected = false): array
    {
        $not = false;
        if ($this->containsNotOperator($filterName)) {
            $filterName = $this->parseFilterName($filterName);
            $not = true;
        }
        if (!isset($this->definedFilters[$filterName])) {
            throw new InvalidArgumentException("Filter $filterName not defined");
        }
        if ($not) {
            return $this->not($filterName, $array, $withRejected);
        }
        $this->reset();
        $filter = $this->definedFilters[$filterName];
        $filter();
        return $this->apply($array, $withRejected);
    }

    /**
     * @return void
     */
    public function resetFilterClosure(): void
    {
        $this->filter = static fn(...$parameters) => true;
    }

    /**
     * @return void
     */
    private function reset(): void
    {
        $this->resetFilterClosure();
        $this->rejected = [];
    }

    /***
     * @param array $filterNames
     * @param array $array
     * @param bool $withRejected
     * @return array
     */
    public function multipleApplyTo(array $filterNames, array $array, bool $withRejected = false): array
    {
        $this->reset();
        $result = [];
        foreach ($filterNames as $name) {
            $result[] = $this->applyTo($name, $array, $withRejected);
        }
        return array_merge(...$result);
    }

    /***
     * @param string $filterName
     * @param array $array
     * @param bool $withRejected
     * @return array
     */
    private function not(string $filterName, array $array, bool $withRejected = false): array
    {
        $this->reset();
        $filter = $this->definedFilters[$filterName];
        $filter();
        $originalFilter = $this->filter;
        $this->filter = static fn($value) => !$originalFilter($value);
        return $this->apply($array, $withRejected);
    }

    /**
     * @param string $operator
     * @return $this
     */
    public function notOperator(string $operator): self
    {
        if (trim($operator) === '') {
            throw new InvalidArgumentException("Operator cannot be empty");
        }
        $this->notOperator = $operator;
        return $this;
    }

    /**
     * @param $filterName
     * @return bool
     */
    public function containsNotOperator($filterName): bool
    {
        return str_starts_with($filterName, $this->notOperator);
    }

    /**
     * @param string $filterName
     * @return string
     */
    public function parseFilterName(string $filterName): string
    {
        return substr($filterName, strlen($this->notOperator));
    }


    /**
     * @param array $array
     * @return $this
     */
    public function defineMultipleFilter(array $array): self
    {
        foreach ($array as $name => $filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                throw new InvalidArgumentException("Filter closure must be an instance of Closure");
            }
            $this->defineFilter($name, fn() => $filterClosure($this));
        }
        return $this;
    }

    /**
     * @param string $filterName
     * @param Closure $closure
     * @return $this
     */
    public function extendDefinedFilter(string $filterName, Closure $closure): self
    {
        if (!isset($this->definedFilters[$filterName])) {
            throw new InvalidArgumentException("Filter $filterName not defined");
        }
        $previousFilter = $this->definedFilters[$filterName];
        $this->definedFilters[$filterName] = function () use ($previousFilter, $closure) {
            $previousFilter();
            $closure($this);
        };
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */

    public function hasDefinedFilter(string $name): bool
    {
        return isset($this->definedFilters[$name]);
    }

    /**
     * @param string $name
     * @return Closure|null
     */
    public function getDefinedFilter(string $name): ?Closure
    {
        return $this->definedFilters[$name] ?? null;
    }

    /**
     * @param string $filter
     * @return self
     */
    public function define(string $filter):self
    {
        //  @todo not yet fully tested
        $filterInstance= new Filter();
        $this->definedFilters[$filter]=function ()use($filterInstance){
            $this->filter = $filterInstance->filter;
        };
        return $filterInstance;
    }

}

