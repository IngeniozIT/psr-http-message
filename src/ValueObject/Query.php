<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject;

readonly final class Query
{
    use WithComplexUriComponent;

    public string $value;

    public function __construct(
        string $value,
    ) {
        $this->value = $this->urlEncodeQueryString($value);
    }

    private function urlEncodeQueryString(string $query): string
    {
        return implode(
            '&',
            array_map(
                fn(string $str) => $this->urlEncodeString($str),
                explode('&', $query)
            )
        );
    }

    /**
     * @return non-empty-string
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getDelimiter(): string
    {
        return '=';
    }

    public function toUriString(): string
    {
        return !empty($this->value) ? '?' . $this : '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
