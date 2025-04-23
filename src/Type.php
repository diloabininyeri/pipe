<?php

namespace Zeus\Pipe;

/**
 *
 */
enum Type
{
    case STRING;
    case INT;
    case FLOAT;
    case BOOL;
    case ARRAY;
    case OBJECT;
    case NULL;

    /**
     * @param mixed $value
     * @return mixed
     */
    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::STRING => (string)$value,
            self::INT => (int)$value,
            self::FLOAT => (float)$value,
            self::BOOL => (bool)$value,
            self::ARRAY => (array)$value,
            self::OBJECT => (object)$value,
            self::NULL => null,
        };
    }
}