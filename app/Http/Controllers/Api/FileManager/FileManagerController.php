<?php

namespace App\Http\Controllers\Api\FileManager;

use App\Helpers\PathHelper;
use App\Http\Controllers\Controller;
use App\Contracts\FileManager;
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
        $request->validate([
            'dir' => 'string|required',
            'options' => 'numeric'
        ]);
        $dir = PathHelper::concat($this->root($id), $request->get('dir'));
        $options = $request->get('options');
        return response()->json($this->FileManager->list($dir, $options));
    }

    public function mkdir($id, Request $request)
    {
        $request->validate([
            'dir' => 'string|required',
            'filename' => 'string|required'
        ]);
        $dir = PathHelper::concat($this->root($id), $request->get('dir'));
        $filename = $request->get('filename');
        return response()->json($this->FileManager->makeDirectory($dir, $filename));
    }

    public function remove($id, Request $request)
    {
        $request->validate([
            'dir' => 'string|required',
            'filenames' => 'array|required'
        ]);
        $dir = PathHelper::concat($this->root($id), $request->get('dir'));
        $filenames = $request->get('filenames');
        return response()->json($this->FileManager->remove($dir, $filenames));
    }

    public function move($id, Request $request)
    {
        $request->validate([
            'fromDir' => 'string|required',
            'toDir' => 'string|required',
            'filenames' => 'array|required',
            'options' => 'numeric'
        ]);
        $fromDir = PathHelper::concat($this->root($id), $request->get('fromDir'));
        $toDir = PathHelper::concat($this->root($id), $request->get('toDir'));
        $filenames = $request->get('filenames');
        $options = $request->get('options');

        return response()->json($this->FileManager->move($fromDir, $toDir, $filenames, $options));
    }

    public function copy($id, Request $request)
    {
        $request->validate([
            'fromDir' => 'string|required',
            'toDir' => 'string|required',
            'filenames' => 'array|required',
            'options' => 'numeric'
        ]);
        $fromDir = PathHelper::concat($this->root($id), $request->get('fromDir'));
        $toDir = PathHelper::concat($this->root($id), $request->get('toDir'));
        $filenames = $request->get('filenames');
        $options = $request->get('options');

        return response()->json($this->FileManager->copy($fromDir, $toDir, $filenames, $options));
    }

    public function rename($id, Request $request)
    {
        $request->validate([
            'dir' => 'string|required',
            'oldFileName' => 'string|required',
            'newFileName' => 'string|required',
        ]);
        $dir = PathHelper::concat($this->root($id), $request->get('dir'));
        $oldFileName = $request->get('oldFileName');
        $newFileName = $request->get('newFileName');

        return response()->json($this->FileManager->rename($dir, $oldFileName, $newFileName));
    }

    public function download($id, Request $request)
    {
        $request->validate([
            'dir' => 'string|required',
            'filenames' => 'array|required'
        ]);
        $dir = PathHelper::concat($this->root($id), $request->get('dir'));
        $filenames = $request->get('filenames');
        $donwloadResult = $this->FileManager->download($dir, $filenames);

        $absolutePath = $donwloadResult['absolutePath'];
        $isTemp = $donwloadResult['isTemp'];

        $response = response()->download($absolutePath, PathHelper::basename($absolutePath));

        if ($isTemp) {
            $response->deleteFileAfterSend();
        }

        return $response;
    }

    public function upload($id, Request $request)
    {
        $request->validate([
            'dir' => 'string|required',
            'options' => 'numeric',
        ]);
        $dir = PathHelper::concat($this->root($id), $request->get('dir'));
        $files = $request->allFiles();
        $options = $request->get('options');

        return response()->json($this->FileManager->upload($dir, $files, $options));
    }
}
