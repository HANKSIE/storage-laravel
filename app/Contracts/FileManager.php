<?php

namespace App\Contracts;

abstract class FileManager
{
    /**
     *
     * @param string $dir
     * 
     * @return array
     */
    abstract public function list($dir);

    /**
     *
     * @param string $dir
     * @param string $filename
     * 
     * @return array
     */
    abstract public function makeDirectory($dir, $filename);

    /**
     *
     * @param string $dir
     * @param string[] $filenames
     * 
     * @return array - 刪除成功的檔案名稱
     */
    abstract public function remove($dir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return array
     */
    abstract public function move($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return array
     */
    abstract public function moveKeepBoth($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return array
     */
    abstract public function moveReplace($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return array
     */
    abstract public function copy($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return array
     */
    abstract public function copyKeepBoth($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @return array
     */
    abstract public function copyReplace($fromDir, $toDir, $filenames);

    /**
     *
     * @param string $dir
     * @param string $oldFileName
     * @param string $newFileName
     * 
     * @return array
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
     * @return array
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
     * @return array
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
     * @return array
     */
    abstract public function uploadReplace($dir, $files);
}
