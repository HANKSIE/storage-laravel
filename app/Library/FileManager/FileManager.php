<?php

namespace App\Library\FileManager;

use App\Contracts\FileManager as ContractsFileManager;
use App\Library\FileManager\Features\Copy;
use App\Library\FileManager\Features\ListDirectory;
use App\Library\FileManager\Features\MakeDirectory;
use App\Library\FileManager\Features\Move;
use App\Library\FileManager\Features\Remove;

class FileManager extends ContractsFileManager
{
    private $features = [];

    public function __construct(
        ListDirectory $ListDirectory,
        MakeDirectory $MakeDirectory,
        Remove $Remove,
        Move $Move,
        Copy $Copy
    ) {
        $this->features['list'] = $ListDirectory;
        $this->features['mkdir'] = $MakeDirectory;
        $this->features['remove'] = $Remove;
        $this->features['move'] = $Move;
        $this->features['copy'] = $Copy;
    }

    public function list($dir)
    {
        return $this->features['list']($dir);
    }

    public function makeDirectory($dir, $filename)
    {
        return $this->features['mkdir']($dir, $filename);
    }

    public function remove($dir, $filenames)
    {
        return $this->features['remove']($dir, $filenames);
    }

    public function move($fromDir, $toDir, $filenames)
    {
        return $this->features['move']($fromDir, $toDir, $filenames);
    }

    public function moveKeepBoth($fromDir, $toDir, $filenames)
    {
    }

    public function moveReplace($fromDir, $toDir, $filenames)
    {
    }

    public function copy($fromDir, $toDir, $filenames)
    {
        return $this->features['copy']($fromDir, $toDir, $filenames);
    }

    public function copyKeepBoth($fromDir, $toDir, $filenames)
    {
    }

    public function copyReplace($fromDir, $toDir, $filenames)
    {
    }

    public function rename($dir, $oldFileName, $newFileName)
    {
    }

    public function download($dir, $filenames)
    {
    }

    public function upload($dir, $files)
    {
    }

    public function uploadKeepBoth($dir, $files)
    {
    }

    public function uploadReplace($dir, $files)
    {
    }
}
