<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;

class Remove extends Feature
{

    public function __invoke(...$args)
    {
        list($dir, $filenames) = $args;

        $filepaths = collect($filenames)->map(function ($filename) use ($dir) {
            return PathHelper::concat($dir, $filename);
        });

        $removeFails = collect();
        $removeSuccesses = collect();
        $notExists = collect();

        $filepaths->each(function ($filepath) use ($removeFails, $removeSuccesses, $notExists) {
            $isSuccess = false;

            if ($this->Storage->exists($filepath)) {
                if ($this->Helper->isDirectory($filepath)) {
                    $isSuccess = $this->Storage->deleteDirectory($filepath);
                } else {
                    $isSuccess = $this->Storage->delete($filepath);
                }

                if (!$isSuccess) {
                    $removeFails->push(PathHelper::basename($filepath));
                } else {
                    $removeSuccesses->push(PathHelper::basename($filepath));
                }
            } else {
                $notExists->push($filepath);
            }
        });

        return [
            'successes' => $removeSuccesses,
            'fails' => $removeFails,
            'notExists' => $notExists
        ];
    }
}
