<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject;

readonly final class Path
{
    use WithComplexUriComponent;

    public string $value;

    public function __construct(
        string $value,
    ) {
        $path = strtolower($this->urlEncodeString($value));
        $this->value = str_starts_with($path, '//') ?
            '/' . ltrim($path, '/') :
            $path;
    }

    public function toUriString(string $authority): string
    {
        return !empty($authority) && !empty($this->value) ?
            '/' . ltrim($this->value, '/') :
            $this->value;
    }

    /**
     * @return non-empty-string
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getDelimiter(): string
    {
        return '/';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
