<?php

namespace App\Contracts;

use App\Library\FileManager\Types\FileInfo;
use App\Library\FileManager\Types\Results\CopyResult;
use App\Library\FileManager\Types\Results\FileInfoResult;
use App\Library\FileManager\Types\Results\FileInfosResult;
use App\Library\FileManager\Types\Results\MoveResult;
use App\Library\FileManager\Types\Results\UploadResult;

abstract class FileManager
{
    /**
     *
     * @param string $dir
     * 
     * @return FileInfosResult
     */
    abstract public function list($dir);

    /**
     *
     * @param string $dir
     * @param string $filename
     * 
     * @return FileInfosResult
     */
    abstract public function makeDirectory($dir, $filename);

    /**
     *
     * @param string $dir
     * @param string[] $filenames
     * 
     * @return FileInfosResult - 刪除成功的檔案名稱
     */
    abstract public function remove($dir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return MoveResult
     */
    abstract public function move($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return FileInfosResult
     */
    abstract public function moveKeepBoth($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return FileInfosResult
     */
    abstract public function moveReplace($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return CopyResult
     */
    abstract public function copy($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return FileInfosResult
     */
    abstract public function copyKeepBoth($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return FileInfosResult
     */
    abstract public function copyReplace($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $dir
     * @param string $oldFileName
     * @param string $newFileName
     * 
     * @return FileInfoResult
     */
    abstract public function rename($dir, $oldFileName, $newFileName);

    /**
     *
     * @param string $dir
     * @param string $filenames
     * 
     * @return string - file absolute path(file or zip file)
     */
    abstract public function download($dir, $filenames);

    /**
     *
     * @param string $dir
     * @param array $files
     * [
     *      path => \Illuminate\Http\UploadedFile
     * ]
     * 
     * @return UploadResult
     */
    abstract public function upload($dir, $files);

    /**
     *
     * @param string $dir
     * @param array $files
     * [
     *      path => \Illuminate\Http\UploadedFile
     * ]
     * 
     * @return UploadResult
     */
    abstract public function uploadKeepBoth($dir, $files);

    /**
     *
     * @param string $dir
     * @param array $files
     * [
     *      path => \Illuminate\Http\UploadedFile
     * ]
     * 
     * @return UploadResult
     */
    abstract public function uploadReplace($dir, $files);
}
