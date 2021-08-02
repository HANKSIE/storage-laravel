<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use App\Library\FileManager\Helper;
use App\Library\FileManager\Types\Results\RemoveResult;
use \Illuminate\Contracts\Filesystem\Filesystem;

class Remove extends Feature
{

    public function __construct(Filesystem $Storage, Helper $Helper)
    {
        parent::__construct($Storage, $Helper);
    }

    public function __invoke(...$args)
    {
        list($dir, $filenames) = $args;

        $filepaths = collect($filenames)->map(function ($filename) use ($dir) {
            return PathHelper::concat($dir, $filename);
        });

        $removeFails = collect();
        $notExists = collect();

        $filepaths->each(function ($filepath) use ($removeFails, $notExists) {
            $isSuccess = false;

            if ($this->Storage->exists($filepath)) {
                if ($this->Helper->isDirectory($filepath)) {
                    $isSuccess = $this->Storage->deleteDirectory($filepath);
                } else {
                    $isSuccess = $this->Storage->delete($filepath);
                }

                if (!$isSuccess) {
                    $removeFails->push(PathHelper::basename($filepath));
                }
            } else {
                $notExists->push($filepath);
            }
        });

        return RemoveResult::make(
            [
                'fails' => $this->Helper->fileInfo($removeFails),
                'notExists' => $notExists->toArray()
            ]
        );
    }
}
