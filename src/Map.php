<?php

namespace Zeus\Pipe;

use Closure;

/**
 *
 */
class Map
{

    /**
     * @var Closure $closure
     */
    private Closure $closure;

    /**
     * @var array $transformers
     */
    private array $transformers = [];

    /**
     *
     */
    public function __construct()
    {
        $this->closure = static fn(mixed $value) => $value;
    }

    /**
     * @noinspection PhpUnused
     * @param Closure $closure
     * @return $this
     */
    public function add(Closure $closure): self
    {
        $previous = $this->closure;
        $this->closure = static fn($value) => $closure($previous($value));
        return $this;
    }

    /**
     * @noinspection PhpUnused
     * @param array $array
     * @return array
     */
    public function apply(array $array): array
    {
        foreach ($this->transformers as $transformer) {
            $array = array_map($transformer, $array);
        }
        return array_map($this->closure, $array);
    }

    /**
     * @param Closure $transform
     * @return $this
     */
    public function transform(Closure $transform): self
    {
        $this->transformers[] = $transform;
        return $this;
    }

    /**
     * @param string $delimiter
     * @return $this
     */
    public function explode(string $delimiter): self
    {
        return $this->transform(function ($value) use ($delimiter) {
            return explode($delimiter, $value);
        });
    }

    /***
     * @param string $delimiter
     * @return self
     */
    public function implode(string $delimiter): self
    {
        return $this->transform(function ($value) use ($delimiter) {
            return implode($delimiter, $value);
        });
    }

    /**
     * @param string $callable
     * @return self
     */
    public function callback(string $callable): self
    {
        return $this->transform(fn($value) => $callable($value));
    }

    /***
     * @param bool $withTransformers
     * @return Closure
     */
    public function getClosure(bool $withTransformers = true): Closure
    {
        if ($withTransformers) {
            return fn(array $array) => $this->apply($array);
        }
        return $this->closure;
    }

    /**
     * @param mixed $default
     * @return $this
     */
    public function default(mixed $default): self
    {
        return $this->transform(fn($value) => $value ?? $default);
    }
}
