<?php

namespace App\Library\FileManager;

use App\Helpers\FileHelper;
use App\Helpers\PathHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Helper
{
    private $Storage;

    public function __construct()
    {
        $this->Storage = Storage::disk(config('filemanager.disk'));
    }

    /**
     *
     * @param string $path
     * @return boolean
     */
    public function isDirectory($path)
    {
        return Str::of($this->Storage->mimeType($path))->is('directory');
    }

    /**
     *
     * @param array $filePaths
     * @return array
     */
    public function fileInfo($filePaths)
    {
        return collect($filePaths)->map(function ($filePath) {
            $mime = $this->Storage->mimeType($filePath);

            return
                [
                    'name' => PathHelper::basename($filePath),
                    'dir' => PathHelper::dirname($filePath),
                    'mime' => $mime,
                    'last_modified' => Carbon::createFromTimestamp(
                        $this->Storage->lastModified($filePath)
                    )->format('Y/m/d h:m'),
                    'size' => $this->isDirectory($filePath) ?
                        (string) Str::of(
                            //該目錄下的檔案/目錄數量
                            collect($this->Storage->directories($filePath))->concat(collect($this->Storage->files($filePath)))->count()
                        )->append(' ', 'items') :
                        //檔案大小
                        FileHelper::formatBytes($this->Storage->size($filePath))
                ];
        })->toArray();
    }

    /**
     *
     * @param string $path
     * @return string
     */
    public function createUniqueName($path)
    {
        //產生該目錄中的唯一檔名
        while ($this->Storage->exists($path)) {
            if ($this->isDirectory($path)) { //dir
                $path = (string)Str::of($path)->append(' ', 'copy');
            } else { //file
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $path = Str::replaceLast(".$ext", "", $path);
                $path = (string)Str::of($path)->append(' ', 'copy.', $ext);
            }
        }

        return $path;
    }
}
