<?php

namespace App\Library\FileManager\Types;

use App\Contracts\Payload;

class FileInfo extends Payload
{
    public static function structure()
    {
        return [
            'name',
            'dir',
            'mime',
            'last_modified',
            'size'
        ];
    }
}
