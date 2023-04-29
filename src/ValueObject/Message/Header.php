<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Message;

use InvalidArgumentException;

readonly final class Header
{
    private const HEADER_INVALID_CHARACTERS = "\0\1\2\3\4\5\6\7\10\11\12\13\14\15\16\17\20\21\22\23\24";
    private const VALUE_INVALID_CHARACTERS = "\0\r\n";

    /** @var string[] */
    public array $value;

    /**
     * @param string|string[] $value
     */
    public function __construct(
        public string $name,
        string|array $value,
    ) {
        $this->assertValidName($name);
        $normalizedValue = $this->normalizeValue($value);
        $this->assertValidValues($normalizedValue);
        $this->value = $normalizedValue;
    }

    private function assertValidName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Header name cannot be empty');
        }
        if (strpbrk($name, self::HEADER_INVALID_CHARACTERS) !== false) {
            throw new InvalidArgumentException('Header name cannot contain control characters');
        }
    }

    /**
     * @param string[] $values
     */
    private function assertValidValues(array $values): void
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Header value cannot be empty');
        }
        array_walk($values, $this->assertValidValue(...));
    }

    private function assertValidValue(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Header value cannot be empty');
        }
        if (strpbrk($value, self::VALUE_INVALID_CHARACTERS) !== false) {
            throw new InvalidArgumentException('Header value cannot contain control characters');
        }
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
        return $name === $this->name && empty(array_diff($this->value, $this->normalizeValue($value)));
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
