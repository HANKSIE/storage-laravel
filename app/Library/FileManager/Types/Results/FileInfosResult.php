<?php

namespace App\Library\FileManager\Types\Results;

use App\Contracts\Payload;
use App\Library\FileManager\Types\FileInfo;

class FileInfosResult extends Payload
{
    public static function structure()
    {
        return [
            'fileInfos' =>
            [
                '*' => FileInfo::structure()
            ],
        ];
    }
}
