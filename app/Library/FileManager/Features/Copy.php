<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\MoveCopy\MoveCopy;
use Illuminate\Support\Facades\File;

class Copy extends MoveCopy
{
    protected function handle($fromPath, $toPath)
    {
        if ($this->Helper->isDirectory($fromPath)) {
            $absoluteFromPath = PathHelper::format($this->Storage->path($fromPath));
            $absoluteToPath = PathHelper::format($this->Storage->path($toPath));
            return File::copyDirectory($absoluteFromPath, $absoluteToPath);
        } else {
            return $this->Storage->copy($fromPath, $toPath);
        }
    }
}
