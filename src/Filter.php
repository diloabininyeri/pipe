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


    public function __construct()
    {
        $this->filter=static fn(...$parameters)=>true;
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
     * @param array $array
     * @return array
     */
    public function apply(array $array): array
    {
        return array_filter($array, $this->filter);
    }

    /**
     * @noinspection PhpUnused
     * @return Closure
     */
    public function getClosure():Closure
    {
        return $this->filter;
    }
}
