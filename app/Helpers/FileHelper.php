<?php

namespace App\Helpers;

class FileHelper
{
    /**
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, $precision = 1)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1000, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
