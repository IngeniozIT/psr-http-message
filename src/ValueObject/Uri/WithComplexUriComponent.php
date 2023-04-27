<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Uri;

trait WithComplexUriComponent
{
    abstract protected function getDelimiter(): string;

    private function urlEncodeString(string $path): string
    {
        $delimiter = $this->getDelimiter();
        return implode(
            $delimiter,
            array_map(
                'rawurlencode',
                array_map(
                    'rawurldecode',
                    explode($delimiter, $path)
                )
            )
        );
    }
}
