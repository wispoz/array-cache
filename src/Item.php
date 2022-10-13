<?php

namespace Wispoz\ArrayCache;

class Item
{

    public function __construct(
        private mixed $value,
        private readonly int $expired,
    )
    {
        $this->value = is_object($value) ? clone $value : $value;
    }
    public function getValue(): mixed
    {
        return is_object( $this->value) ? clone  $this->value :  $this->value;
    }
    public function isExpired(int $time): bool
    {
        return $this->expired < $time;
    }
}