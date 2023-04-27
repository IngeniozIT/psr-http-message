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
        $this->value = $this->normalizeValue($value);
    }

    /**
     * @param string|string[] $value
     */
    public function withAddedValue(array|string $value): self
    {
        return new self(
            $this->name,
            array_unique(array_merge($this->value, $this->normalizeValue($value)))
        );
    }

    /**
     * @param string|string[] $value
     */
    public function has(array|string $value): bool
    {
        foreach ($this->normalizeValue($value) as $v) {
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
        return empty(array_diff($this->value, $this->normalizeValue($value)));
    }

    /**
     * @param string|string[] $value
     * @return string[]
     */
    private function normalizeValue(string|array $value): array
    {
        return is_string($value) ? [$value] : array_values($value);
    }
}
