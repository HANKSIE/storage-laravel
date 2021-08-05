<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class Zip extends Feature
{

    private $ZipArchive;
    private $absolutePath;
    private $absoluteTempPath;

    public function __construct(ZipArchive $ZipArchive)
    {
        $this->absolutePath = $this->Storage->path('');
        $this->absoluteTempPath = Storage::disk('filemanager.temp_disk')->path('');
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
            $absolutePath =  PathHelper::concat($this->absolutePath, $filePath);
            if (File::exists($absolutePath)) {
                if (File::isDirectory($absolutePath)) {
                    //mkdir
                    $this->ZipArchive->addEmptyDir((string)Str::of($filePath)->after($rootDir));

                    $innerFilePaths = collect(
                        File::files($filePath)
                    )->concat(
                        File::directories($filePath)
                    );

                    // continue..
                    $this->recursion_zip($rootDir, $innerFilePaths);
                } else {
                    // add file
                    $this->ZipArchive->addFromString(
                        (string)Str::of($filePath)->after($rootDir),
                        File::get($absolutePath)
                    );
                }
            }
        });
    }
}
