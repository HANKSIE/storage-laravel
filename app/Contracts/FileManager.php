<?php

namespace App\Contracts;

abstract class FileManager
{
    const LIST_ALL = 0;
    const LIST_DIR_ONLY = 1;
    const LIST_FILE_ONLY = 2;

    const OVERRIDE_NONE = 0;
    const OVERRIDE_KEEPBOTH = 1;
    const OVERRIDE_REPLACE = 2;

    const ACTION_COPY = 0;
    const ACTION_MOVE = 1;

    /**
     *
     * @param string $dir
     * @param int $options
     * 
     * @return array
     */
    abstract public function list($dir, $options = self::LIST_ALL);

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
     * @param int $options
     * @return array
     */
    abstract public function move($fromDir, $toDir, $filenames, $options = self::OVERRIDE_NONE);

    /**
     *
     * @param string $fromDir
     * @param string $toDir
     * @param string[] $filenames
     * @param int $options
     * @return array
     */
    abstract public function copy($fromDir, $toDir, $filenames, $options = self::OVERRIDE_NONE);

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
     * @param int $options
     * 
     * @return array
     */
    abstract public function upload($dir, $files, $options = self::OVERRIDE_NONE);
}
