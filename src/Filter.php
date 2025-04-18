<?php

namespace Zeus\Pipe;

use Closure;

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
    private ?Closure $ddClosure=null;

    /**
     *
     */
    public function __construct()
    {
        $this->filter = static fn(...$parameters) => true;
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
            return $this->filterWithRejected($array, $applyRejected);
        }

        $filtered= array_filter($array, $this->filter);
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
            $args = func_get_args();
            return !$callback(...$args);
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
        $this->ddClosure = static function ($value) use($debug){
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


}

