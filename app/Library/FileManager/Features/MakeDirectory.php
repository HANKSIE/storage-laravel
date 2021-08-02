<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use App\Library\FileManager\Types\Results\FileInfoResult;


class MakeDirectory extends Feature
{
    public function __invoke(...$args)
    {
        list($dir, $dirname) = $args;
        $dirpath = PathHelper::concat($dir, $dirname);
        $this->Storage->makeDirectory($dirpath);
        return FileInfoResult::make(
            [
                'fileInfo' =>  $this->Helper->fileInfo([$dirpath])[0],
            ]
        );
    }
}
