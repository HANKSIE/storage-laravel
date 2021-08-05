<?php

namespace App\Library\FileManager;

use App\Contracts\FileManager as ContractsFileManager;
use App\Library\FileManager\Features\Copy;
use App\Library\FileManager\Features\Download;
use App\Library\FileManager\Features\ListDirectory;
use App\Library\FileManager\Features\MakeDirectory;
use App\Library\FileManager\Features\Move;
use App\Library\FileManager\Features\Remove;
use App\Library\FileManager\Features\Rename;
use App\Library\FileManager\Features\Upload;

class FileManager extends ContractsFileManager
{
    private $features = [];

    public function __construct(
        ListDirectory $ListDirectory,
        MakeDirectory $MakeDirectory,
        Remove $Remove,
        Move $Move,
        Copy $Copy,
        Rename $Rename,
        Download $Download,
        Upload $Upload
    ) {
        $this->features['list'] = $ListDirectory;
        $this->features['mkdir'] = $MakeDirectory;
        $this->features['remove'] = $Remove;
        $this->features['move'] = $Move;
        $this->features['copy'] = $Copy;
        $this->features['rename'] = $Rename;
        $this->features['download'] = $Download;
        $this->features['upload'] = $Upload;
    }

    public function list($dir, $options = self::LIST_ALL)
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

    public function move($fromDir, $toDir, $filenames, $options = self::OVERRIDE_NONE)
    {
        return $this->features['move']($fromDir, $toDir, $filenames, $options);
    }

    public function copy($fromDir, $toDir, $filenames, $options = self::OVERRIDE_NONE)
    {
        return $this->features['copy']($fromDir, $toDir, $filenames, $options);
    }

    public function rename($dir, $oldFileName, $newFileName)
    {
        return $this->features['rename']($dir, $oldFileName, $newFileName);
    }

    public function download($dir, $filenames)
    {
        return $this->features['download']($dir, $filenames);
    }

    public function upload($dir, $files, $options = self::OVERRIDE_NONE)
    {
        return $this->features['upload']($dir, $files, $options);
    }
}
