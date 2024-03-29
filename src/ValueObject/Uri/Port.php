<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Uri;

use InvalidArgumentException;

readonly final class Port
{
    public function __construct(
        public ?int $value,
    ) {
        if ($value !== null && ($value < 1 || $value > 65535)) {
            throw new InvalidArgumentException('Port must be between 1 and 65535');
        }
    }

    public function toUriString(): string
    {
        return $this->value !== null ? ':' . $this : '';
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
