<?php

namespace App\Library\FileManager\Types\Results;

use App\Contracts\Payload;

class FileInfoResult extends Payload
{
    public static function structure()
    {
        return [
            'fileInfo'
        ];
    }
}
