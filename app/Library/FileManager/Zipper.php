<?php

namespace App\Library\FileManager;

use App\Helpers\PathHelper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class Zipper
{
    private $ZipArchive;
    private $absolutePath;
    private $absoluteTempPath;

    public function __construct($absolutePath, $absoluteTempPath)
    {
        $this->absolutePath = $absolutePath;
        $this->absoluteTempPath = $absoluteTempPath;
        $this->ZipArchive = new ZipArchive();
    }

    /**
     *
     * @param string $dir
     * @param array $filenames
     * @return string - zip absolute path
     */
    public function zip($dir, $filenames)
    {
        $dir = PathHelper::format($dir);
        $zipPath = PathHelper::concat($this->absoluteTempPath, uniqid() . ".zip");

        $filepaths = collect($filenames)->map(function ($filename) use ($dir) {
            return PathHelper::concat($dir, $filename);
        });

        $this->ZipArchive->open($zipPath,  ZipArchive::CREATE);
        $this->recursion_zip($dir, $filepaths);
        $this->ZipArchive->close();
        return $zipPath;
    }

    private function recursion_zip($rootDir, $filepaths)
    {
        collect($filepaths)->each(function ($filepath) use ($rootDir) {
            $absolutePath =  PathHelper::concat($this->absolutePath, $filepath);
            if (File::exists($absolutePath)) {
                if (File::isDirectory($absolutePath)) {
                    // get filepaths & folders in dir

                    //mkdir
                    $this->ZipArchive->addEmptyDir((string)Str::of($filepath)->after($rootDir));

                    $innerFilePaths = collect(
                        File::files($filepath)
                    )->concat(
                        File::directories($filepath)
                    );

                    // continue..
                    $this->recursion_zip($rootDir, $innerFilePaths);
                } else {
                    // add file
                    $this->ZipArchive->addFromString(
                        (string)Str::of($filepath)->after($rootDir),
                        File::get($absolutePath)
                    );
                }
            }
        });
    }
}
