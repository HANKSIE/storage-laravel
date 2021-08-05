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

        if (!($exist = $this->Storage->exists($newFilePath))) { {
                $absoluteOldeFilePath = $this->Storage->path($oldFilePath);
                $absoluteNewFilePath = $this->Storage->path($newFilePath);
                $isSuccess = rename($absoluteOldeFilePath, $absoluteNewFilePath);
            }
        }

        return [
            'exist' => $exist,
            'isSuccess' => $isSuccess
        ];
    }
}
