<?php

namespace App\Http\Controllers\Api\FileManager;

class UserFileManagerController extends AuthorizeFileManagerController
{
    protected function root($id)
    {
        return "user/$id/root";
    }

    protected function auth($id)
    {
        $this->authorize('filemanager-user-access', [$id]);
    }
}
