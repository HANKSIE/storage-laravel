<?php

namespace App\Library\FileManager\Types\Results;

use App\Contracts\Payload;
use App\Library\FileManager\Types\FileInfo;

class MoveResult extends Payload
{
    public static function structure()
    {
        return [
            'exists' =>
            [
                '*' => FileInfo::structure()
            ],
            'selfs' =>
            [
                '*' => FileInfo::structure()
            ],
        ];
    }
}
