<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Uri;

use InvalidArgumentException;

readonly final class Scheme
{
    public string $value;

    public function __construct(
        string $value,
    ) {
        $value = strtolower($value);
        if (!empty($value) && !preg_match('/^[a-z][a-z0-9+-.]+$/', $value)) {
            throw new InvalidArgumentException("Invalid scheme '{$value}'");
        }
        $this->value = $value;
    }

    public function defaultPort(): ?int
    {
        $port = getservbyname($this->value, 'tcp') ?: getservbyname($this->value, 'udp');
        return $port !== false ? $port : null;
    }

    public function toUriString(): string
    {
        return !empty($this->value) ? $this . ':' : '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
