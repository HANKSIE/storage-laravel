<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class PathHelper
{
    public static function basename($path)
    {
        if (Str::of($path)->contains('/')) {
            return (string)Str::of($path)->afterLast('/');
        } else {
            return $path;
        }
    }

    public static function pathRoot($path)
    {
        $path = Str::of(self::pathFormat($path));
        if (!$path->contains('/')) {
            return '';
        }

        if (((string)$path)[0] === '/') {
            $path = $path->after('/');
        }

        return explode('/', (string)$path)[0];
    }

    // 反斜線/連續反斜線/連續斜線 => 單個斜線
    public static function pathFormat($path)
    {
        $path = preg_replace('/\\\\+/', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        return $path;
    }

    public static function concatPath()
    {
        $args = collect(func_get_args());
        $result = Str::of($args->shift());
        $args->each(function ($path) use (&$result) {
            $result = $result->append('/', $path);
        });
        return self::pathFormat((string) $result);
    }

    public static function isEqual()
    {
        $isEqual = false;
        $paths = collect(func_get_args());
        $first = self::pathFormat($paths->shift());

        $paths->each(function ($path) use (&$isEqual, $first) {
            $path = self::pathFormat($path);
            $isEqual = dirname($first) === dirname($path) && basename($first) === basename($path);
        });

        return $isEqual;
    }
}
