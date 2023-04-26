<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject;

use InvalidArgumentException;

readonly final class Header
{
    /** @var string[] */
    public array $value;

    /**
     * @param string|string[] $value
     */
    public function __construct(
        public string $name,
        string|array $value,
    ) {
        if (empty($name)) {
            throw new InvalidArgumentException('Header name cannot be empty');
        }
        if (empty($value)) {
            throw new InvalidArgumentException('Header value cannot be empty');
        }
        if (is_string($value)) {
            $value = [$value];
        }
        $this->value = array_values($value);
    }

    /**
     * @param string|string[] $value
     */
    public function withAddedValue(array|string $value): self
    {
        if (is_string($value)) {
            $value = [$value];
        }
        return new self($this->name, array_unique(array_merge($this->value, $value)));
    }

    /**
     * @param string|string[] $value
     */
    public function has(array|string $value): bool
    {
        if (is_string($value)) {
            $value = [$value];
        }
        foreach ($value as $v) {
            if (!in_array($v, $this->value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string|string[] $value
     */
    public function equals(string $name, array|string $value): bool
    {
        if ($name !== $this->name) {
            return false;
        }
        if (is_string($value)) {
            $value = [$value];
        }
        return empty(array_diff($this->value, $value));
    }
}
