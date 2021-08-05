<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class PathHelper
{
    public static function extension($path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return $ext;
    }

    public static function dirname($path)
    {
        return dirname($path);
    }

    public static function basename($path)
    {
        if (Str::of($path)->contains('/')) {
            return (string)Str::of($path)->afterLast('/');
        } else {
            return $path;
        }
    }

    public static function rootFileName($path)
    {
        $path = Str::of(self::format($path));
        if (!$path->contains('/')) {
            return '';
        }

        $firstCh = ((string)$path)[0];

        if ($firstCh === '/') {
            $path = $path->after('/');
        }

        return explode('/', (string)$path)[0];
    }

    // 反斜線/連續反斜線/連續斜線 => 單個斜線
    public static function format($path)
    {
        $path = preg_replace('/\\\\+/', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        return $path;
    }

    public static function concat(...$args)
    {
        $args = collect($args);
        $result = Str::of($args->shift());
        $args->each(function ($path) use (&$result) {
            $result = $result->append('/', $path);
        });
        return self::format((string) $result);
    }

    public static function equal(...$args)
    {
        $isEqual = false;
        $paths = collect($args);
        $first = self::format($paths->shift());

        $paths->each(function ($path) use (&$isEqual, $first) {
            $path = self::format($path);
            $isEqual = dirname($first) === dirname($path) && basename($first) === basename($path);
        });

        return $isEqual;
    }
}
