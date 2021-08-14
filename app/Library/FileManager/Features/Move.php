<?php

namespace App\Library\FileManager\Features;

use App\Contracts\FileManager;
use App\Helpers\PathHelper;
use App\Library\FileManager\Features\MoveCopy\MoveCopy;
use Illuminate\Support\Facades\File;

class Move extends MoveCopy
{
    protected function action()
    {
        return FileManager::ACTION_MOVE;
    }
}
