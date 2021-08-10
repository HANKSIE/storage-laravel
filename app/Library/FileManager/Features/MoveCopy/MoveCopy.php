<?php

namespace App\Library\FileManager\Features\MoveCopy;

use App\Contracts\FileManager;
use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use Illuminate\Support\Str;

abstract class MoveCopy extends Feature
{

    public function __invoke(...$args)
    {
        list($fromDir, $toDir, $filenames, $options) = $args;

        $fileDatas = $this->getFileDatas($fromDir, $toDir, $filenames);

        $exists = $this->getHasExists($fileDatas);

        $notExists = $this->getNotExists($fileDatas);

        $selfs = $this->getHasSelfs($fileDatas);

        $canHandle = collect($fileDatas)->filter(function ($data) use ($selfs) {
            return !$selfs->contains($data['filename']);
        })->filter(function ($data) use ($notExists) {
            return !$notExists->contains($data['filename']);
        });

        switch ($options) {
            case FileManager::OVERRIDE_NONE:
                $canHandle = $canHandle->filter(function ($data) use ($exists) {
                    return !$exists->contains($data['filename']);
                });
                break;
            case FileManager::OVERRIDE_KEEPBOTH:
                $canHandle = $canHandle->map(function ($data) {
                    return array_merge(
                        $data,
                        [
                            'toPath' => $this->Helper->createUniqueName($data['toPath'])
                        ]
                    );
                });
                break;
            case FileManager::OVERRIDE_REPLACE:
                // delete toPath files
                $canHandle->each(function ($data) {
                    $toPath = $data['toPath'];
                    if ($this->Helper->isDirectory($toPath)) {
                        $this->Storage->deleteDirectory($toPath);
                    } else {
                        $this->Storage->delete($toPath);
                    }
                });
                break;
        }

        $successHandleFilePaths = $canHandle->filter(function ($data) {
            return $this->handle($data['fromPath'], $data['toPath']);
        })->map(function ($successHandleFileData) {
            return $successHandleFileData['toPath'];
        });;

        return [
            'fileInfos' => $this->Helper->fileInfo($successHandleFilePaths),
            'exists' => $exists,
            'notExists' => $notExists,
            'selfs' => $selfs,
        ];
    }

    /**
     *
     * @param string $fromPath
     * @param string $toPath
     * @return boolean
     */
    abstract protected function handle($fromPath, $toPath);

    private function getFileDatas($fromDir, $toDir, $filenames)
    {
        return collect($filenames)->map(function ($filename) use ($fromDir, $toDir) {
            $fromPath = PathHelper::concat($fromDir, $filename);
            $toPath = PathHelper::concat($toDir, $filename);
            return [
                'fromPath' => $fromPath,
                'toPath' => $toPath,
                'filename' => $filename,
            ];
        });
    }

    private function getHasExists($fileDatas)
    {
        return collect($fileDatas)->filter(function ($data) {
            return $this->Storage->exists($data['toPath']);
        })->map(function ($data) {
            return $data['filename'];
        });
    }

    private function getNotExists($fileDatas)
    {
        return collect($fileDatas)->filter(function ($data) {
            return !$this->Storage->exists($data['fromPath']);
        })->map(function ($data) {
            return $data['filename'];
        });
    }

    private function getHasSelfs($fileDatas)
    {
        return collect($fileDatas)->filter(function ($data) {
            return ($this->Helper->isDirectory($data['fromPath']) &&
                Str::of($data['toPath'])->contains(
                    $data['fromPath']
                ));
        })->map(function ($data) {
            return $data['filename'];
        });
    }
}
