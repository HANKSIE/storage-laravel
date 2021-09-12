<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;

class Remove extends Feature
{

    public function __invoke(...$args)
    {
        list($dir, $filenames) = $args;

        $removeFails = collect();
        $removeSuccesses = collect();
        $notExists = collect();

        collect($filenames)->each(function ($filename) use ($dir, $removeFails, $removeSuccesses, $notExists) {
            $filepath = PathHelper::concat($dir, $filename);
            $isSuccess = false;
            if ($this->Storage->exists($filepath)) {
                if ($this->Helper->isDirectory($filepath)) {
                    $isSuccess = $this->Storage->deleteDirectory($filepath);
                } else {
                    $isSuccess = $this->Storage->delete($filepath);
                }

                if (!$isSuccess) {
                    $removeFails->push($filename);
                } else {
                    $removeSuccesses->push($filename);
                }
            } else {
                $notExists->push($filename);
            }
        });

        return [
            'successes' => $removeSuccesses,
            'fails' => $removeFails,
            'notExists' => $notExists
        ];
    }
}
