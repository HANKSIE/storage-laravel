<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use App\Library\FileManager\Types\Results\FileInfosResult;

class ListDirectory extends Feature
{
    const BOTH = 0;
    const DIR_ONLY = 1;
    const FILE_ONLY = 2;

    public function __invoke(...$args)
    {
        $dir = $args[0] ?? '';
        $only_flag = $args[1] ?? self::BOTH;

        $dir = PathHelper::format($dir);
        $dirs = collect($this->Storage->directories($dir));
        $files = collect($this->Storage->files($dir));

        switch ($only_flag) {
            case self::BOTH:
                $items = $dirs->concat($files);
                break;
            case self::DIR_ONLY:
                $items = $dirs;
                break;
            case self::FILE_ONLY:
                $items = $files;
                break;
            default:
                $items = $dirs->concat($files);
                break;
        }

        $fileInfos = $this->Helper->fileInfo($items);

        return FileInfosResult::make(
            [
                'fileInfos' => $fileInfos,
            ]
        );
    }
}
