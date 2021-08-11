<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;

class MakeDirectory extends Feature
{
    public function __invoke(...$args)
    {
        list($dir, $dirname) = $args;
        $dirpath = PathHelper::concat($dir, $dirname);
        $isSuccess = false;
        if (!($exist = $this->Storage->exists($dirpath))) {
            $isSuccess = $this->Storage->makeDirectory($dirpath);
        }

        $fileInfo = $isSuccess ?
            $this->Helper->fileInfo([$dirpath])[0]
            : null;

        return [
            'exist' => $exist,
            'isSuccess' => $isSuccess,
            'fileInfo' => $fileInfo,
        ];
    }
}
