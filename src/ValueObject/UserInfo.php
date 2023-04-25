<?php

namespace IngeniozIT\Http\Message\ValueObject;

readonly final class UserInfo
{
    public string $value;

    public function __construct(
        public string $user,
        public ?string $password,
    ) {
        $this->value = $this->user . ($this->password !== null ? ':' . $this->password : '');
    }

    public function toUriString(): string
    {
        return !empty($this->value) ? $this . '@' : '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
