<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Message;

readonly final class Headers
{
    /**
     * @param Header[] $headers
     */
    public function __construct(
        public array $headers,
    ) {
    }

    /**
     * @return string[]
     */
    public function getHeaderValues(string $name): array
    {
        return $this->headers[$this->normalizeName($name)]->value ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            $headers[$header->name] = $header->value;
        }
        return $headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$this->normalizeName($name)]);
    }

    /**
     * @param string|string[] $value
     */
    public function hasHeaderEqualTo(string $name, array|string $value): bool
    {
        $header = $this->headers[$this->normalizeName($name)] ?? null;
        return $header !== null && $header->equals($name, $value);
    }

    /**
     * @param string|string[] $value
     */
    public function hasHeaderWithValue(string $name, array|string $value): bool
    {
        $header = $this->headers[$this->normalizeName($name)] ?? null;
        return $header !== null && $header->has($value);
    }

    public function withoutHeader(string $name): self
    {
        $headers = $this->headers;
        unset($headers[$this->normalizeName($name)]);
        return new self($headers);
    }

    /**
     * @param string|string[] $value
     */
    public function withHeader(string $name, string|array $value): self
    {
        $headers = $this->headers;
        $headers[$this->normalizeName($name)] = new Header($name, $value);
        return new self($headers);
    }

    /**
     * @param string|string[] $value
     */
    public function withHeaderValue(string $name, string|array $value): self
    {
        $headers = $this->headers;
        $headers[$this->normalizeName($name)] = $headers[$this->normalizeName($name)]->withAddedValue($value);
        return new self($headers);
    }

    private function normalizeName(string $name): string
    {
        return strtolower($name);
    }
}
