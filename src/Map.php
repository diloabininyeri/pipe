<?php

namespace Zeus\Pipe;

use Closure;

/**
 *
 */
class Map
{

    /**
     * @var Closure
     */
    private Closure $closure;

    /**
     *
     */
    public function __construct()
    {
        $this->closure=static fn(mixed $value)=>$value;
    }

    /**
     * @noinspection PhpUnused
     * @param Closure $closure
     * @return $this
     */
    public function add(Closure $closure):self
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
    public function apply(array $array):array
    {
        return array_map($this->closure, $array);
    }

    /**
     * @noinspection PhpUnused
     * @return Closure
     */
    public function getClosure(): Closure
    {
        return $this->closure;
    }
}
