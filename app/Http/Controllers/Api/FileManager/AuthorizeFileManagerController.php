<?php

namespace App\Http\Controllers\Api\FileManager;

use App\Library\FileManager;
use Illuminate\Support\Facades\Route;

abstract class AuthorizeFileManagerController extends FileManagerController
{
    /**
     * 定義根目錄
     *
     * @param int $id
     * @return string
     */
    abstract protected function root($id);

    /**
     * Gate/Policy authorize
     *
     * @param int $id
     * @return void
     */
    abstract protected function auth($id);

    public function __construct(FileManager $FileManager)
    {
        parent::__construct($FileManager);

        $id = Route::current()->parameter('id');
        $this->auth($id);
    }
}
