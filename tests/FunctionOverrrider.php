<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Tests\Message;

/**
 * Used to mock calls to native PHP functions.
 */
class NativeFunctionsMocker
{
    public static $overrides = [];

    public static function resetAll(): void
    {
        self::$overrides = [];
    }

    public static function makeFunctionReturn(string $name, $return): void
    {
        self::$overrides[$name] = $return;
    }

    public static function makeFunctionsReturn(array $functions): void
    {
        foreach ($functions as $name => $return) {
            self::makeFunctionReturn($name, $return);
        }
    }
}

// ========================================== //
// Filesystem overrides                       //
// ========================================== //

namespace IngeniozIT\Http\Message;

use IngeniozIT\Http\Tests\Message\NativeFunctionsMocker;

function fread($resource, $length)
{
    return NativeFunctionsMocker::$overrides['fread'] ?? \fread($resource, $length);
}

function fwrite($resource, $string)
{
    return NativeFunctionsMocker::$overrides['fwrite'] ?? \fwrite($resource, $string);
}

function fstat($resource)
{
    return NativeFunctionsMocker::$overrides['fstat'] ?? \fstat($resource);
}

function ftell($resource)
{
    return NativeFunctionsMocker::$overrides['ftell'] ?? \ftell($resource);
}

function stream_get_meta_data($resource)
{
    return NativeFunctionsMocker::$overrides['stream_get_meta_data'] ?? \stream_get_meta_data($resource);
}
function rename(string $oldname, string $newname)
{
    return NativeFunctionsMocker::$overrides['rename'] ?? \rename($oldname, $newname);
}

function is_uploaded_file(string $filename)
{
    return NativeFunctionsMocker::$overrides['is_uploaded_file'] ?? \is_uploaded_file($filename);
}

function move_uploaded_file(string $filename, string $destination)
{
    return NativeFunctionsMocker::$overrides['move_uploaded_file'] ?? \move_uploaded_file($filename, $destination);
}

function file_put_contents($filename, $data)
{
    return NativeFunctionsMocker::$overrides['file_put_contents'] ?? \file_put_contents($filename, $data);
}

function fopen(string $path, $mode)
{
    return NativeFunctionsMocker::$overrides['fopen'] ?? \fopen($path, $mode);
}

function fclose($handle)
{
    return NativeFunctionsMocker::$overrides['fclose'] ?? \fclose($handle);
}

function realpath(string $path)
{
    return NativeFunctionsMocker::$overrides['realpath'] ?? \realpath($path);
}

function php_sapi_name()
{
    return NativeFunctionsMocker::$overrides['php_sapi_name'] ?? \php_sapi_name();
}

function is_resource($var)
{
    return NativeFunctionsMocker::$overrides['is_resource'] ?? \is_resource($var);
}
