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

        if ($exist = $this->Storage->exists($dirpath)) {
            $this->Storage->makeDirectory($dirpath);
        }

        return
            [
                'fileInfo' =>  $this->Helper->fileInfo([$dirpath])[0],
                'exist' => $exist
            ];
    }
}
