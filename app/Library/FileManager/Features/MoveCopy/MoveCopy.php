<?php

namespace App\Library\FileManager\Features\MoveCopy;

use App\Contracts\FileManager;
use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class MoveCopy extends Feature
{

    public function __invoke(...$args)
    {
        list($fromDir, $toDir, $filenames, $options) = $args;

        $fromDir = PathHelper::format($fromDir);
        $toDir = PathHelper::format($toDir);

        if ($this->action() == FileManager::ACTION_MOVE && $fromDir === $toDir) {
            abort(422, 'cannot move when "from dir" = "to dir"');
        }

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

                if ($fromDir !== $toDir) {
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
        }

        $successHandleFilePaths =
            ($fromDir === $toDir && $options == FileManager::ACTION_MOVE) ?
            $canHandle->map(function ($data) {
                return $data['toPath'];
            }) :
            $canHandle->filter(function ($data) {
                if ($this->action() === FileManager::ACTION_COPY) {
                    return $this->copy($data['fromPath'], $data['toPath']);
                }
                if ($this->action() === FileManager::ACTION_MOVE) {
                    return $this->move($data['fromPath'], $data['toPath']);
                }
            })->map(function ($successHandleFileData) {
                return $successHandleFileData['toPath'];
            });

        return [
            'fileInfos' => $this->Helper->fileInfo($successHandleFilePaths),
            'exists' => $exists,
            'notExists' => $notExists,
            'selfs' => $selfs,
        ];
    }

    /**
     *
     * @return string|int
     */
    abstract protected function action();

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
            if ($this->Storage->exists($data['fromPath'])) {
                return ($this->Helper->isDirectory($data['fromPath']) &&
                    $data['fromPath'] !== $data['toPath'] &&
                    Str::of($data['toPath'])->contains(
                        $data['fromPath']
                    ));
            }
            return false;
        })->map(function ($data) {
            return $data['filename'];
        })->values();
    }

    protected function copy($fromPath, $toPath)
    {
        if ($this->Helper->isDirectory($fromPath)) {
            $absoluteFromPath = PathHelper::format($this->Storage->path($fromPath));
            $absoluteToPath = PathHelper::format($this->Storage->path($toPath));
            return File::copyDirectory($absoluteFromPath, $absoluteToPath);
        } else {
            return $this->Storage->copy($fromPath, $toPath);
        }
    }

    protected function move($fromPath, $toPath)
    {
        if ($this->Helper->isDirectory($fromPath)) {
            $absoluteFromPath = PathHelper::format($this->Storage->path($fromPath));
            $absoluteToPath = PathHelper::format($this->Storage->path($toPath));
            return File::moveDirectory($absoluteFromPath, $absoluteToPath);
        } else {
            return $this->Storage->move($fromPath, $toPath);
        }
    }
}
