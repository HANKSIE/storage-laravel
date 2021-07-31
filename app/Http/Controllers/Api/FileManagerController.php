<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PathHelper;
use App\Http\Controllers\Controller;
use App\Services\FileManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

abstract class FileManagerController extends Controller
{
    protected $FileManagerService;

    public function __construct(FileManagerService $FileManagerService)
    {
        $this->FileManagerService = $FileManagerService;
    }

    /**
     *
     * @param int $id
     * @return string
     */
    abstract protected function root($id);

    public function list($id, Request $request)
    {
        $dir = $request->dir;
        return response()->json($this->FileManagerService->ls(PathHelper::concatPath($this->root($id), $dir), $request->only), Response::HTTP_OK);
    }

    public function mkdir($id, Request $request)
    {
        $dir = $request->dir;
        $dirname = $request->dirname;
        $rootDir = $this->root($id);
        if (Storage::exists(PathHelper::concatPath($rootDir, $dir, $dirname))) {
            return response(null, Response::HTTP_FORBIDDEN);
        }
        return response()->json(
            $this->FileManagerService->mkdir($this->root($id), $dir, $dirname),
            Response::HTTP_OK
        );
    }

    public function remove($id, Request $request)
    {
        $dir = $request->dir;
        $this->FileManagerService->rm(
            PathHelper::concatPath($this->root($id), $dir),
            $request->file_list
        );
        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function move($id, Request $request)
    {
        $from_dir = $request->from_dir;
        $to_dir = $request->to_dir;
        $data = $this->FileManagerService->mv(
            PathHelper::concatPath($this->root($id), $from_dir),
            PathHelper::concatPath($this->root($id), $to_dir),
            $request->file_list,
            $request->override_flag
        );
        return response()->json($data, Response::HTTP_OK);
    }

    public function copy($id, Request $request)
    {
        $from_dir = $request->from_dir;
        $to_dir = $request->to_dir;
        $data = $this->FileManagerService->cp(
            PathHelper::concatPath($this->root($id), $from_dir),
            PathHelper::concatPath($this->root($id), $to_dir),
            $request->file_list,
            $request->override_flag
        );
        return response($data, Response::HTTP_OK);
    }

    public function rename($id, Request $request)
    {
        $dir = $request->dir;
        $data = $this->FileManagerService->rename(
            PathHelper::concatPath($this->root($id), $dir),
            $request->old_file,
            $request->new_file
        );
        return response($data, Response::HTTP_OK);
    }

    public function download($id, Request $request)
    {
        $dir = $request->dir;
        $root = $this->root($id);
        $files = collect($request->file_list);
        $rootWithDir = PathHelper::concatPath($root, $dir);

        //only 1 file
        if ($files->count() === 1) {
            $file = $files->first();
            $fileWithRoot = PathHelper::concatPath($rootWithDir, $file);

            if (!Storage::disk('public')->exists($fileWithRoot)) {
                return response('File does not exist.', Response::HTTP_NOT_FOUND);
            }

            $filePath = Storage::disk('public')->path($fileWithRoot);

            //dir
            if ($this->FileManagerService->isDirectory($fileWithRoot)) {
                $zipPath = $this->FileManagerService->zip($rootWithDir, $request->file_list);
                return response()->file($zipPath, [
                    'Content-Disposition' => urlencode(basename($zipPath)),
                ])->deleteFileAfterSend();
            }

            return response()->file($filePath, [
                'Content-Disposition' => urlencode(basename($filePath)),
            ]);
        } else {
            //return zip
            $zipPath = $this->FileManagerService->zip($rootWithDir, $files);
            return response()->file($zipPath, [
                'Content-Disposition' => urlencode(basename($zipPath)),
            ])->deleteFileAfterSend();
        }
    }

    public function upload($id, Request $request)
    {
        $dir = $request->dir;
        $rootWithDir = PathHelper::concatPath($this->root($id), $dir);
        $files = $request->allFiles();
        $override_flag = $request->override_flag;
        return $this->FileManagerService->upload($rootWithDir, $files, $override_flag);
    }
}
