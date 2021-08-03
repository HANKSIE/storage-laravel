<?php

namespace App\Library\FileManager\Features\MoveCopy;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;

abstract class MoveCopy extends Feature
{

    public function __invoke(...$args)
    {
        list($fromDir, $toDir, $filenames) = $args;

        $exists = collect();
        $notExists = collect();
        $selfs = collect();
        $fails = collect();

        collect($filenames)->each(function ($filename) use ($fromDir, $toDir, $exists, $notExists, $selfs, $fails) {
            $fromPath = PathHelper::concat($fromDir, $filename);
            $toPath = PathHelper::concat($toDir, $filename);

            if (!$this->Storage->exists($fromPath)) {
                $notExists->push($filename);
                return;
            }

            if ($this->isSelf($fromPath, $toPath, $filename)) {
                $selfs->push($filename);
                return;
            }

            if ($this->Storage->exists($toPath)) {
                $exists->push($toPath);
                return;
            }

            if (!$this->handle($fromPath, $toPath)) {
                $fails->push($filename);
            }
        });
    }

    /**
     *
     * @param string $fromPath
     * @param string $toPath
     * @return boolean
     */
    abstract protected function handle($fromPath, $toPath);

    private function isSelf($fromPath, $toPath, $filename)
    {
        return  $this->Helper->isDirectory($fromPath) &&
            PathHelper::equal(
                PathHelper::concat($fromPath, $filename),
                $toPath
            );
    }
}
