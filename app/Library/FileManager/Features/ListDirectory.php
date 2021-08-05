<?php

namespace App\Library\FileManager\Features;

use App\Contracts\FileManager;
use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;

class ListDirectory extends Feature
{

    public function __invoke(...$args)
    {
        list($dir, $options) = $args;

        $dir = PathHelper::format($dir);
        $dirs = collect($this->Storage->directories($dir));
        $files = collect($this->Storage->files($dir));

        switch ($options) {
            case FileManager::LIST_ALL:
                $items = $dirs->concat($files);
                break;
            case FileManager::LIST_DIR_ONLY:
                $items = $dirs;
                break;
            case FileManager::LIST_FILE_ONLY:
                $items = $files;
                break;
            default:
                $items = $dirs->concat($files);
                break;
        }

        $fileInfos = $this->Helper->fileInfo($items);

        return [
            'fileInfos' => $fileInfos,
        ];
    }
}
