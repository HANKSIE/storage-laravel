<?php

namespace App\Http\Controllers\Api\FileManager;

use Illuminate\Http\Request;

class UserFileManagerController extends AuthorizeFileManagerController
{
    protected function root($id)
    {
        return "user/$id/files";
    }

    protected function auth($id)
    {
        abort_if(auth()->id() != $id, 403);
    }
}
