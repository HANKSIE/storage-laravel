<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PathHelper;
use App\Models\Team;

class UserFileManagerController extends FileManagerController
{
    protected $authorizeModel = Team::class;

    protected function root($id)
    {
        return "user/$id/root";
    }
}
