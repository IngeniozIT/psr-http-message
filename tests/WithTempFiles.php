<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use RuntimeException;

trait WithTempFiles
{
    protected static string $file;
    protected static string $availableFile;

    public static function setUpBeforeClass(): void
    {
        $file = tempnam(sys_get_temp_dir(), uniqid());
        if ($file === false) {
            throw new RuntimeException('Could not generate temporary file');
        }
        self::$file = $file;

        $availableFile = tempnam(sys_get_temp_dir(), uniqid());
        if ($availableFile === false) {
            throw new RuntimeException('Could not generate temporary file');
        }
        self::$availableFile = $availableFile;
        unlink(self::$availableFile);
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$file)) {
            unlink(self::$file);
        }
    }

    public function tearDown(): void
    {
        if (file_exists(self::$availableFile)) {
            unlink(self::$availableFile);
        }
    }

    /**
     * @return resource
     */
    protected static function open(string $file, string $mode)
    {
        $resource = fopen($file, $mode);

        if (!is_resource($resource)) {
            throw new RuntimeException("Could not open {$file}");
        }

        return $resource;
    }

    /**
     * @return resource
     */
    protected static function nonSeekableResource()
    {
        $resource = popen('echo foo 1> /dev/null', 'w');

        if (!is_resource($resource)) {
            throw new RuntimeException("Could not create non seekable resource");
        }

        return $resource;
    }
}
