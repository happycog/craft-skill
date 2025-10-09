<?php

if (! function_exists('throw_if')) {
    /**
     * Throw the given exception if the given condition is true.
     *
     * @template TValue
     * @template TException of \Throwable
     *
     * @param  TValue  $condition
     * @param  TException|class-string<TException>|string  $exception
     * @param  mixed  ...$parameters
     * @return ($condition is true ? never : ($condition is non-empty-mixed ? never : TValue))
     *
     * @throws TException
     */
    function throw_if($condition, $exception = 'RuntimeException', ...$parameters)
    {
        if ($condition) {
            if (is_string($exception) && class_exists($exception)) {
                $exception = new $exception(...$parameters);
            }

            if (is_string($exception)) {
                throw new \RuntimeException($exception);
            } elseif ($exception instanceof \Throwable) {
                throw $exception;
            } else {
                throw new \RuntimeException('Invalid exception type');
            }
        }

        return $condition;
    }
}

if (! function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     *
     * @template TValue
     * @template TException of \Throwable
     *
     * @param  TValue  $condition
     * @param  TException|class-string<TException>|string  $exception
     * @param  mixed  ...$parameters
     * @return ($condition is false ? never : ($condition is non-empty-mixed ? TValue : never))
     *
     * @throws TException
     */
    function throw_unless($condition, $exception = 'RuntimeException', ...$parameters)
    {
        throw_if(! $condition, $exception, ...$parameters);

        return $condition;
    }
}

if (! function_exists('collect')) {
    /**
     * Create a new collection from the given items.
     *
     * @param array<mixed> $items
     * @return Collection
     */
    function collect(array $items = []): Collection
    {
        return new Collection($items);
    }
}

/**
 * Simple collection class for helper methods.
 */
class Collection
{
    /** @var array<mixed> */
    private array $items;

    /** @param array<mixed> $items */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param string|callable $key
     * @return self
     */
    public function keyBy($key): self
    {
        $keyed = [];
        foreach ($this->items as $item) {
            if (is_callable($key)) {
                $keyValue = $key($item);
            } elseif (is_object($item) && property_exists($item, $key)) {
                $keyValue = $item->$key;
            } else {
                $keyValue = $key;
            }
            $keyed[$keyValue] = $item;
        }
        return new self($keyed);
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }
}