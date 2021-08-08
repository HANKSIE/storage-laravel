<?php

namespace App\Library\FileManager\Features;

use App\Contracts\FileManager;
use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use Illuminate\Support\Str;
use Ds\Set;

class Upload extends Feature
{
    public function __invoke(...$args)
    {
        list($dir, $files, $options) = $args;

        $files = $this->formatFiles($files);

        $filePaths = $files->keys();

        $rootFileNames = $this->getRootFileNames($filePaths);

        $exists = collect($rootFileNames)->filter(function ($filename) use ($dir) {
            $filePath = PathHelper::concat($dir, $filename);
            return $this->Storage->exists($filePath);
        });

        switch ($options) {
            case FileManager::OVERRIDE_NONE:
                $files = $files->filter(function ($file, $filePath) use ($exists) {
                    return !$exists->contains($filePath);
                });
                break;
            case FileManager::OVERRIDE_KEEPBOTH:
                $files = $files->mapWithKeys(function ($file, $filePath) {
                    $oldRootFileName = PathHelper::rootFileName($filePath);

                    $newFilePath = $this->Helper->createUniqueName($filePath);
                    $newRootFileName = PathHelper::rootFileName($newFilePath);
                    $newRootFilePath = (string)Str::of($oldRootFileName)->replaceFirst(
                        $oldRootFileName,
                        $newRootFileName
                    );

                    return [$newRootFilePath => $file];
                });
                break;
            case FileManager::OVERRIDE_REPLACE:
                $exists->each(function ($filePath) {
                    if ($this->Helper->isDirectory($filePath)) {
                        $this->Storage->deleteDirectory($filePath);
                    } else {
                        $this->Storage->delete($filePath);
                    }
                });
                break;
        }

        //save files
        $fails = $files->filter(function ($file, $filePath) use ($dir) {
            $filePath = PathHelper::concat($dir, $filePath);
            return !$this->Storage->putFileAs(
                PathHelper::dirname($filePath),
                $file,
                PathHelper::basename($filePath)
            );
        })->keys();

        $failsRootFileNames = $this->getRootFileNames($fails);

        $rootFilePaths = collect($rootFileNames)
            ->filter(function ($rootFileName) use ($failsRootFileNames) {
                return !collect($failsRootFileNames)->contains($rootFileName);
            })
            ->map(function ($rootFileName) use ($dir) {
                return PathHelper::concat($dir, $rootFileName);
            });

        return [
            'fails' => $fails,
            'exists' => $exists,
            'fileInfos' => $this->Helper->fileInfo($rootFilePaths),
        ];
    }

    private function formatFiles($files)
    {
        return collect($files)->mapWithKeys(function ($file, $filePath) {
            //[filename]_[ext] => [filename].[ext]
            $originName = $file->getClientOriginalName();
            $filePath = (string)Str::of($filePath)->replaceLast(PathHelper::basename($filePath), $originName);
            return [$filePath => $file];
        });
    }

    private function getRootFileNames($filePaths)
    {
        $rootFileNames = new Set(
            collect($filePaths)->map(function ($filePath) {
                return PathHelper::rootFileName($filePath);
            })->toArray()
        );

        return $rootFileNames->toArray();
    }
}
