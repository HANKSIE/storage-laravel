<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;

class Rename extends Feature
{

    public function __invoke(...$args)
    {
        list($dir, $oldFileName, $newFileName) = $args;

        $oldFilePath = PathHelper::concat($dir, $oldFileName);
        $newFilePath = PathHelper::concat($dir, $newFileName);

        $isSuccess = false;

        if (!($exist = $this->Storage->exists($newFilePath))) {
            $isSuccess = $this->Storage->move($oldFilePath, $newFilePath);
        }

        $newFileInfo = $isSuccess ?
            $this->Helper->fileInfo([$newFilePath])[0]
            : null;

        return [
            'exist' => $exist,
            'isSuccess' => $isSuccess,
            'fileInfo' => $newFileInfo
        ];
    }
}
