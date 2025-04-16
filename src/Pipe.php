<?php

namespace Zeus\Pipe;

use Closure;
use Throwable;

class Pipe
{
    /**
     * @var array
     */
    private array $middlewares = [];
    /**
     * @var ?Closure $withoutClosure
     */
    private ?Closure $withoutClosure = null;

    /**
     * @var ?Closure $onlyClosure
     */
    private ?Closure $onlyClosure = null;

    /**
     * @var ?Closure $catchClosure
     */
    private ?Closure $catchClosure = null;

    /**
     * @var ?Closure $watchClosure
     */
    private ?Closure $watchClosure = null;

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * @param string $name
     * @param callable $middleware
     * @return $this
     */
    public function next(string $name, callable $middleware): self
    {
        $this->middlewares[$name] = $middleware;
        return $this;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function run(mixed $data): mixed
    {
        if ($this->catchClosure) {
            try {
                return $this->executePipeline($data);
            } catch (Throwable $throwable) {
                return call_user_func($this->catchClosure, $throwable);
            }
        }
        return $this->executePipeline($data);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function executePipeline(mixed $data): mixed
    {
        $result = $data;
        $filteredMiddlewares = $this->getFilteredMiddlewares();


        $isStopped = false;
        $stop = static function (mixed $value)use(&$isStopped){
            $isStopped = true;
            return $value;
        };

        foreach ($filteredMiddlewares as $name => $middleware) {
            if ($isStopped) {
                break;
            }
            $beforeValue = $result;
            $afterValue = $middleware($result, function ($transformedData){
                return $transformedData;
            },$stop);

            if ($this->watchClosure) {
                call_user_func($this->watchClosure, $name, $beforeValue, $afterValue);
            }

            $result = $afterValue;
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getFilteredMiddlewares(): array
    {
        $filteredMiddlewares = [];

        foreach ($this->middlewares as $name => $middleware) {
            if ($this->withoutClosure && !call_user_func($this->withoutClosure, $name)) {
                continue;
            }

            if ($this->onlyClosure && !call_user_func($this->onlyClosure, $name)) {
                continue;
            }

            $filteredMiddlewares[$name] = $middleware;
        }

        return $filteredMiddlewares;
    }

    /**
     * @return void
     */
    public function flush(): void
    {
        $this->middlewares = [];
    }

    /**
     * @return callable
     */
    public function getClosure(): callable
    {
        return function ($data) {
            return $this->executePipeline($data);
        };
    }

    /**
     * @param array $array
     * @return $this
     */
    public function only(array $array): self
    {
        $this->onlyClosure = static function (mixed $name) use ($array) {
            return in_array($name, $array, true);
        };
        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function catch(Closure $closure): self
    {
        $this->catchClosure = $closure;
        return $this;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function watch(callable $callback): self
    {
        $this->watchClosure = $callback;
        return $this;
    }
}