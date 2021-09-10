<?php

namespace Tests\Feature;

use App\Contracts\FileManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FileManagerControllerTest extends TestCase
{
    use RefreshDatabase;
    private $FileManager;
    private $User;
    private $MainStorage;
    private $TempStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->FileManager = $this->app->make(FileManager::class);
        $this->User = User::factory()->create();
        $mainDisk = config('filemanager.disk');
        $tempDisk = config('filemanager.temp_disk');

        Storage::fake($mainDisk);
        Storage::fake($tempDisk);

        $this->MainStorage = Storage::disk($mainDisk);
        $this->TempStorage = Storage::disk($tempDisk);

        $userID = $this->User->id;
        $this->MainStorage->makeDirectory("user/$userID");
        $this->initUserRootStructure($userID);

        $this->actingAs($this->User);
    }

    private function userRoot($userID)
    {
        return "user/$userID/files";
    }

    private function createDirStructure(string $dir, array $filenames = [])
    {
        $this->MainStorage->makeDirectory($dir);

        foreach ($filenames as $filename) {
            $file = UploadedFile::fake()->create($filename);
            $this->MainStorage->putFileAs($dir, $file, $filename);
        }

        return $this;
    }

    private function initUserRootStructure($userID)
    {
        $root = $this->userRoot($userID);

        $this->createDirStructure($root, [
            '.gitignore',
            'tsconfig.json',
            'main.js',
            'timer.js'
        ])
            ->createDirStructure("$root/other", ['1.txt'])
            ->createDirStructure("$root/utils", ['timer.js', 'router.js'])
            ->createDirStructure("$root/utils/other")
            ->createDirStructure("$root/src", ['main.js', 'App.vue'])
            ->createDirStructure("$root/src/utils", ['timer.js', 'router.js'])
            ->createDirStructure("$root/src/utils/other");
    }

    public function test_list_all()
    {
        $userID = $this->User->id;

        $res = $this->postJson("api/user/$userID/files", [
            'dir' => '/',
            'options' => FileManager::LIST_ALL
        ]);

        $res->assertOk()
            ->assertJsonStructure(
                [
                    'fileInfos' => [
                        '*' => ['name', 'mime', 'lastModified', 'size']
                    ]
                ]
            )->assertJsonCount(7, 'fileInfos')
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'other')
                        ->where('mime', 'directory')
                        ->where('size', '1 items')
                        ->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json
                        ->where('name', 'src')
                        ->where('mime', 'directory')
                        ->where('size', '3 items')
                        ->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
                    $json
                        ->where('name', 'utils')
                        ->where('mime', 'directory')
                        ->where('size', '3 items')
                        ->etc();
                });
                $json->has('fileInfos.3', function (AssertableJson $json) {
                    $json
                        ->where('name', '.gitignore')
                        ->where('mime', 'text/plain')
                        ->etc();
                });
                $json->has('fileInfos.4', function (AssertableJson $json) {
                    $json
                        ->where('name', 'main.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.5', function (AssertableJson $json) {
                    $json
                        ->where('name', 'timer.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.6', function (AssertableJson $json) {
                    $json
                        ->where('name', 'tsconfig.json')
                        ->where('mime', 'application/json')
                        ->etc();
                });
            });
    }

    public function test_list_dir_only()
    {
        $userID = $this->User->id;

        $res = $this->postJson("api/user/$userID/files", [
            'dir' => '/',
            'options' => FileManager::LIST_DIR_ONLY
        ]);

        $res->assertOk()
            ->assertJsonCount(3, 'fileInfos')
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'other')
                        ->where('mime', 'directory')
                        ->where('size', '1 items')
                        ->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json
                        ->where('name', 'src')
                        ->where('mime', 'directory')
                        ->where('size', '3 items')
                        ->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
                    $json
                        ->where('name', 'utils')
                        ->where('mime', 'directory')
                        ->where('size', '3 items')
                        ->etc();
                });
            });
    }

    public function test_list_file_only()
    {
        $userID = $this->User->id;

        $res = $this->postJson("api/user/$userID/files", [
            'dir' => '/',
            'options' => FileManager::LIST_FILE_ONLY
        ]);

        $res->assertOk()
            ->assertJsonCount(4, 'fileInfos')
            ->assertJson(function (AssertableJson $json) {

                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', '.gitignore')
                        ->where('mime', 'text/plain')
                        ->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json
                        ->where('name', 'main.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
                    $json
                        ->where('name', 'timer.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.3', function (AssertableJson $json) {
                    $json
                        ->where('name', 'tsconfig.json')
                        ->where('mime', 'application/json')
                        ->etc();
                });
            });
    }

    public function test_mkdir()
    {
        $userID = $this->User->id;
        $res = $this->postJson("api/user/$userID/files/mkdir", ['filename' => 'unique_dir']);

        $res
            ->assertOk()
            ->assertJsonStructure([
                'exist',
                'isSuccess',
                'fileInfo'
            ])->assertJson(function (AssertableJson $json) {
                $json->where('exist', false)
                    ->where('isSuccess', true);
                $json->has('fileInfo', function (AssertableJson $json) {
                    $json
                        ->where('name', 'unique_dir')
                        ->where('mime', 'directory')
                        ->etc();
                });
            });
    }

    public function test_mkdir_repeat()
    {
        $userID = $this->User->id;
        $res = $this->postJson("api/user/$userID/files/mkdir", ['filename' => 'src']);

        $res
            ->assertOk()
            ->assertExactJson([
                'exist' => true,
                'isSuccess' => false,
                'fileInfo' => null
            ]);
    }

    public function test_remove()
    {
        $userID = $this->User->id;
        $res = $this->deleteJson("api/user/$userID/files/remove", [
            'dir' => '/',
            'filenames' => ['src', 'tsconfig.json', 'notExistFile.txt', 'notExistDir'],
        ]);
        $res->assertOk()
            ->assertExactJson([
                'successes' => ['src', 'tsconfig.json'],
                'fails' => [],
                'notExists' => ['notExistFile.txt', 'notExistDir']
            ]);
    }

    public function test_move()
    {
        $userID = $this->User->id;
        $res = $this->putJson("api/user/$userID/files/move", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['src', 'timer.js', 'notExist', 'tsconfig.json'],
            'options' => FileManager::OVERRIDE_NONE
        ]);

        $res->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'tsconfig.json')
                        ->where('mime', 'application/json')
                        ->etc();
                });
                $json->where('exists', ['timer.js']);
                $json->where('notExists', ['notExist']);
                $json->where('selfs', ['src']);
            });
    }

    public function test_move_keepboth()
    {
        $userID = $this->User->id;
        $res = $this->putJson("api/user/$userID/files/move", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['timer.js'],
            'options' => FileManager::OVERRIDE_KEEPBOTH
        ]);
        $res->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'timer copy.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->where('exists', ['timer.js']);
                $json->where('notExists', []);
                $json->where('selfs', []);
            });
    }

    public function test_move_replace()
    {
        $userID = $this->User->id;
        $res = $this->putJson("api/user/$userID/files/move", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['other'],
            'options' => FileManager::OVERRIDE_REPLACE
        ]);

        $res->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'other')
                        ->where('size', '1 items')
                        ->etc();
                });
                $json->where('exists', ['other']);
                $json->where('notExists', []);
                $json->where('selfs', []);
            });
    }
}
