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
                    $rootFileName = PathHelper::rootFileName($filePath);
                    return !$exists->contains($rootFileName);
                });
                break;
            case FileManager::OVERRIDE_KEEPBOTH:
                $files = $files->mapWithKeys(function ($file, $filePath) use ($dir) {
                    $oldRootFileName = PathHelper::rootFileName($filePath);
                    $oldRootFilePath = PathHelper::concat($dir, $oldRootFileName);
                    $newRootFilePath = $this->Helper->createUniqueName($oldRootFilePath);
                    $newRootFileName = PathHelper::basename($newRootFilePath);
                    $newRootFilePath = (string)Str::of($filePath)->replaceFirst(
                        $oldRootFileName,
                        $newRootFileName
                    );

                    return [$newRootFilePath => $file];
                });
                break;
            case FileManager::OVERRIDE_REPLACE:
                $exists->each(function ($filePath) use ($dir) {
                    $filePath = PathHelper::concat($dir, $filePath);
                    if ($this->Storage->exists($filePath)) {
                        if ($this->Helper->isDirectory($filePath)) {
                            $this->Storage->deleteDirectory($filePath);
                        } else {
                            $this->Storage->delete($filePath);
                        }
                    }
                });
                break;
        }

        $fails = collect();
        $successes = collect();
        //save files
        $files->each(function ($file, $filePath) use ($dir, $fails, $successes) {
            $uploadFilePath = PathHelper::concat($dir, $filePath);
            $isSuccess = $this->Storage->putFileAs(
                PathHelper::dirname($uploadFilePath),
                $file,
                PathHelper::basename($uploadFilePath)
            );

            if ($isSuccess) {
                $successes->push($filePath);
            } else {
                $fails->push($filePath);
            }
        });

        $successRootFileNames = $this->getRootFileNames($successes);

        $rootFilePaths = collect($successRootFileNames)->map(function ($rootFileName) use ($dir) {
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
