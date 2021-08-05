<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use App\Library\FileManager\Helper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class Zip extends Feature
{

    private $ZipArchive;
    private $absoluteTempPath;

    public function __construct(Helper $Helper, ZipArchive $ZipArchive)
    {
        parent::__construct($Helper);
        $this->absoluteTempPath = Storage::disk(config('filemanager.temp_disk'))->path('');
        $this->ZipArchive = $ZipArchive;
    }

    /**
     *
     * @param string $dir
     * @param array $filenames
     * @return string - zip absolute path
     */
    public function __invoke(...$args)
    {
        list($dir, $filenames) = $args;

        $dir = PathHelper::format($dir);
        $zipPath = PathHelper::concat($this->absoluteTempPath, uniqid() . ".zip");

        $filePaths = collect($filenames)->map(function ($filename) use ($dir) {
            return PathHelper::concat($dir, $filename);
        });

        $this->ZipArchive->open($zipPath,  ZipArchive::CREATE);
        $this->recursion_zip($dir, $filePaths);
        $this->ZipArchive->close();
        return $zipPath;
    }

    private function recursion_zip($rootDir, $filePaths)
    {
        collect($filePaths)->each(function ($filePath) use ($rootDir) {
            if ($this->Storage->exists($filePath)) {
                if ($this->Helper->isDirectory($filePath)) {
                    //mkdir
                    $this->ZipArchive->addEmptyDir((string)Str::of($filePath)->after($rootDir));

                    $innerFilePaths = collect(
                        $this->Storage->files($filePath)
                    )->concat(
                        $this->Storage->directories($filePath)
                    );

                    // continue..
                    $this->recursion_zip($rootDir, $innerFilePaths);
                } else {
                    // add file
                    $this->ZipArchive->addFromString(
                        (string)Str::of($filePath)->after($rootDir),
                        $this->Storage->get($filePath)
                    );
                }
            }
        });
    }
}
