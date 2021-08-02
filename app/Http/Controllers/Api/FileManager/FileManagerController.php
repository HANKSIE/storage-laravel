<?php

namespace App\Http\Controllers\Api\FileManager;

use App\Http\Controllers\Controller;
use App\Library\FileManager\FileManager;
use Illuminate\Http\Request;

abstract class FileManagerController extends Controller
{
    protected $FileManager;

    public function __construct(FileManager $FileManager)
    {
        $this->FileManager = $FileManager;
    }

    /**
     *
     * @param int $id
     * @return string
     */
    abstract protected function root($id);

    public function list($id, Request $request)
    {
    }

    public function mkdir($id, Request $request)
    {
    }

    public function remove($id, Request $request)
    {
    }

    public function move($id, Request $request)
    {
    }

    public function copy($id, Request $request)
    {
    }

    public function rename($id, Request $request)
    {
    }

    public function download($id, Request $request)
    {
    }

    public function upload($id, Request $request)
    {
    }
}
