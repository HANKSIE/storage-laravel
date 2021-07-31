<?php

namespace App\Services;

use App\Helpers\PathHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Ds\Set;

class FileManagerService
{
    const BOTH = 0;
    const DIR_ONLY = 1;
    const FILE_ONLY = 2;

    const OVERRIDE_NONE = 0;
    const OVERRIDE_KEEPBOTH = 1;
    const OVERRIDE_REPLACE = 2;

    const ACTION_MOVE = 'move';
    const ACTION_COPY = 'copy';

    private $ZipArchive;
    private $Storage;
    private $TempStorage;

    public function __construct($disk = 'local', ZipArchive $ZipArchive)
    {
        $this->Storage = Storage::disk($disk);
        $this->TempStorage = Storage::disk('temp');
        $this->ZipArchive = $ZipArchive;
    }

    public function __call($name, $args)
    {
        $cp_mv = collect(['mv' => self::ACTION_MOVE, 'cp' => self::ACTION_COPY]);
        if ($cp_mv->keys()->contains($name)) {
            array_unshift($args, $cp_mv->get($name));
            return call_user_func_array([$this, 'cp_mv'], $args);
        }
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
     * get total items in directory
     *
     * @param string $dirpath
     * @return int
     */
    private function totalItems($dirpath)
    {
        return collect($this->Storage->directories($dirpath))->concat(collect($this->Storage->files($dirpath)))->count();
    }

    /**
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 1)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     *
     * @param array $items
     * @return array
     */
    private function data($items)
    {
        return collect($items)->map(function ($item) {
            $mimeType = $this->Storage->mimeType($item);
            return [
                'name' => PathHelper::basename($item),
                'mimeType' => $mimeType,
                'last_modified' => Carbon::createFromTimestamp($this->Storage->lastModified($item))->format('Y/m/d h:m'),
                'size' => $this->isDirectory($item) ?
                    (string) Str::of($this->totalItems($item))->append(' ', 'items') :
                    $this->formatBytes($this->Storage->size($item))
            ];
        })->toArray();
    }

    /**
     * @param string $dir
     * @param boolean $only
     * @return void
     */
    public function ls($dir = '', $only_flag = self::BOTH)
    {
        $dir = PathHelper::pathFormat($dir);
        $dirs = collect($this->Storage->directories($dir));
        $files = collect($this->Storage->files($dir));

        switch ($only_flag) {
            case self::BOTH:
                $items = $dirs->concat($files);
                break;
            case self::DIR_ONLY:
                $items = $dirs;
                break;
            case self::FILE_ONLY:
                $items = $files;
                break;
            default:
                $items = $dirs->concat($files);
                break;
        }

        $items = $this->data($items);

        return [
            'items' => $items,
        ];
    }

    /**
     * Undocumented function
     *
     * @param string $rootDir
     * @param string $path
     * @param string $dirname
     * @return array
     */
    public function mkdir($rootDir, $dir = '', $dirname)
    {
        $rootDir = PathHelper::pathFormat($rootDir);
        $dir = PathHelper::pathFormat($dir);
        $dirpath = PathHelper::concatPath($rootDir, $dir, $dirname);
        $this->Storage->makeDirectory($dirpath);
        return [
            'dir' =>  $this->data([$dirpath])[0],
        ];
    }

    /**
     *
     * @param string $rootDir
     * @param array $files
     * @return void
     */
    public function rm($dir, $files)
    {
        $dir = PathHelper::pathFormat($dir);
        $files = collect($files)->map(function ($f) use ($dir) {
            return PathHelper::concatPath($dir, $f);
        });

        $files->each(function ($f) {
            if ($this->isDirectory($f)) {
                $this->Storage->deleteDirectory($f);
            } else {
                $this->Storage->delete($f);
            }
        });
    }

    private function createUniqueName($path)
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

    /**
     * @param string $action - "move" or "copy"
     * @param string $fromDir
     * @param string $toDir
     * @param array $files
     * @param int $override_flag
     * @return array|void - if $override_flag is 0 return a array, otherwise void.
     */
    public function cp_mv($action, $fromDir, $toDir, $files, $override_flag = self::OVERRIDE_NONE)
    {
        $fromDir = PathHelper::pathFormat($fromDir);
        $toDir = PathHelper::pathFormat($toDir);

        $hasExists = collect();
        $newFiles = collect();
        $actionSelfs = collect();

        collect($files)->each(function ($f) use ($fromDir, $toDir, $hasExists, $action, $override_flag, $newFiles, $actionSelfs) {
            $from = PathHelper::concatPath($fromDir, $f);
            $to = PathHelper::concatPath($toDir, $f);

            $exist = false;

            if (
                $this->isDirectory($from) &&
                $override_flag == self::OVERRIDE_REPLACE
            ) {
                throw new Exception('\"from dir\" cannot equal to \"to dir\" when \"replace\".');
            }

            if (!$this->Storage->exists($from)) {
                return;
            }

            if ($this->Storage->exists($to)) {
                if ($override_flag == self::OVERRIDE_NONE) {
                    $hasExists->push($f);
                    return;
                }
                $exist = true;
            }

            // copy or move into self
            if (
                $this->isDirectory($from) &&
                PathHelper::isEqual(PathHelper::concatPath($from, $f), $to)
            ) {
                $actionSelfs->push($f);
                return;
            }

            if ($exist && $override_flag == self::OVERRIDE_KEEPBOTH) {
                $to = $this->createUniqueName($to);
            }

            if ($exist && !PathHelper::isEqual($fromDir, $toDir) && $override_flag == self::OVERRIDE_REPLACE) {
                if ($this->isDirectory($from)) {
                    $this->Storage->deleteDirectory($to);
                } else {
                    $this->Storage->delete($to);
                }
            }

            if (!(PathHelper::isEqual($fromDir, $toDir) && $action === self::ACTION_MOVE)) {
                if ($this->isDirectory($from)) {
                    File::{$action . 'Directory'}(PathHelper::pathFormat($this->path($from)), PathHelper::pathFormat($this->path($to)));
                } else {
                    $this->Storage->{$action}($from, $to);
                }
                $newFiles->push($to);
            }
        });

        if ($override_flag == self::OVERRIDE_NONE) {
            return [
                'exists' => $hasExists,
                'action_selfs' => $actionSelfs,
            ];
        }

        return [
            'files' => $this->data($newFiles),
            'action_selfs' => $actionSelfs,
        ];
    }

    public function rename($dir, $old, $new)
    {
        $dir = PathHelper::pathFormat($dir);
        if ($this->Storage->exists(PathHelper::concatPath($dir, $new))) {
            return [
                'exist' => true
            ];
        } else {
            rename(
                $this->path(PathHelper::concatPath($dir, $old)),
                $this->path(PathHelper::concatPath($dir, $new)),
            );
            return [
                'exist' => false,
                'file' => $this->data([PathHelper::concatPath($dir, $new)])[0]
            ];
        }
    }

    /**
     *
     * @param string $dir
     * @param array $files
     * @return string - zip path
     */
    public function zip($dir, $files)
    {
        $dir = PathHelper::pathFormat($dir);
        $zipPath = $this->temp_path(uniqid() . ".zip");

        $files = collect($files)->map(function ($f) use ($dir) {
            return PathHelper::concatPath($dir, $f);
        });

        $this->ZipArchive->open($zipPath,  ZipArchive::CREATE);
        $this->recursion_zip($dir, $files);
        $this->ZipArchive->close(); //save zip file
        return $zipPath;
    }

    private function recursion_zip($rootDir, $files)
    {
        collect($files)->each(function ($f) use ($rootDir) {
            if ($this->Storage->exists($f)) {
                if ($this->isDirectory($f)) {
                    // get files & folders
                    $this->ZipArchive->addEmptyDir((string)Str::of($f)->after($rootDir));
                    $dirFiles = collect(
                        $this->Storage->files($f)
                    )->concat(
                        $this->Storage->directories($f)
                    );
                    $this->recursion_zip($rootDir, $dirFiles);
                } else {
                    $this->ZipArchive->addFromString(
                        (string)Str::of($f)->after($rootDir),
                        $this->Storage->get($f)
                    );
                }
            }
        });
    }

    public function upload($dir, $files, $override_flag = self::OVERRIDE_NONE)
    {
        //此處的$override_flag 需要以"=="運算子比較，$override_flag可能為string || int 
        $rootItems = new Set();
        $files = collect($files)->mapWithKeys(function ($file, $path) use ($rootItems) {
            //[filename]_[ext] => [filename].[ext]
            $originName = $file->getClientOriginalName();
            $path = (string)Str::of($path)->replaceLast(PathHelper::basename($path), $originName);
            // get root item
            $pathRoot = PathHelper::pathRoot($path);
            $rootItems->add(empty($pathRoot) ? $path : $pathRoot);
            return [$path => $file];
        });

        $rootItems = $rootItems->toArray();

        $exists = collect();
        $uploadRootItemPaths = collect();

        collect($rootItems)->each(function ($rootItem) use ($dir, $override_flag, $exists, $uploadRootItemPaths, &$files) {
            $rootItemPath = PathHelper::concatPath($dir, $rootItem);

            if ($this->Storage->exists($rootItemPath)) {
                $exists->push($rootItem);

                switch ($override_flag) {
                    case self::OVERRIDE_NONE:
                        $files = $files->filter(function ($file, $path) use ($rootItem) {
                            return PathHelper::pathRoot($path) !== $rootItem;
                        });
                        break;
                    case self::OVERRIDE_KEEPBOTH:
                        $newItemPath = $this->createUniqueName($rootItemPath);
                        //不需要路徑，只需要檔名 
                        $uploadRootItemPaths->put($rootItem, PathHelper::basename($newItemPath));
                        break;
                    case self::OVERRIDE_REPLACE:
                        //delete exists files/folders
                        if ($this->isDirectory($rootItemPath)) {
                            $this->Storage->deleteDirectory($rootItemPath);
                        } else {
                            $this->Storage->delete($rootItemPath);
                        }
                        $uploadRootItemPaths->push($rootItem);
                        break;
                }
            } else {
                $uploadRootItemPaths->push($rootItem);
            }
        });

        if ($override_flag == self::OVERRIDE_KEEPBOTH) {
            $files = $files->mapWithKeys(function ($file, $path) use ($uploadRootItemPaths) {
                $rootItem = PathHelper::pathRoot($path);
                if ($uploadRootItemPaths->has($rootItem)) {
                    $newRootItem = $uploadRootItemPaths->get($rootItem);
                    // rootItem => newRootItem, e.g: /path/to/file => /path copy/to/file
                    $newRootPath = (string)Str::of($path)->replaceFirst($rootItem, $newRootItem);
                    return [$newRootPath => $file];
                }
                return [$path => $file];
            });
        }


        $files->each(function ($file, $path) use ($dir) {
            $path = PathHelper::concatPath($dir, $path);
            $this->Storage->putFileAs(dirname($path), $file, PathHelper::basename($path));
        });

        if ($override_flag == self::OVERRIDE_NONE || $override_flag == self::OVERRIDE_REPLACE) {
            $uploadFiles = $this->data(
                $uploadRootItemPaths->map(function ($path) use ($dir) {
                    return PathHelper::concatPath($dir, $path);
                })
            );
        } else { // key value pair
            $uploadFiles = $this->data(
                $uploadRootItemPaths->mapWithKeys(function ($new, $old) use ($dir) {
                    return [PathHelper::concatPath($dir, $new)];
                })
            );
        }


        return [
            'exists' => $exists,
            'upload_files' => $uploadFiles,
        ];
    }

    public function path($path)
    {
        return $this->Storage->path($path);
    }

    public function temp_path($path)
    {
        return $this->TempStorage->path($path);
    }
}
