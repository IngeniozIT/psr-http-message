<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject;

readonly final class Fragment
{
    public function __construct(
        public string $value,
    ) {
    }

    public function toUriString(): string
    {
        return !empty($this->value) ? '#' . $this : '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
