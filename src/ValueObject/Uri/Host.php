<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Uri;

use InvalidArgumentException;

readonly final class Host
{
    public string $value;

    public function __construct(
        string $value,
    ) {
        $value = strtolower($value);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException("Invalid host '{$value}'");
        }
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
