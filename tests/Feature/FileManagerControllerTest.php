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

    private function createDirStructure(string $root = '/', array $struct)
    {
        $this->MainStorage->makeDirectory($root);

        foreach ($struct as $k => $v) {
            if (is_array($v)) {
                $dir = "$root/$k";
                $this->createDirStructure($dir, $v);
            } else {
                $file = UploadedFile::fake()->create($v);
                $this->MainStorage->putFileAs($root, $file, $v);
            }
        }
    }

    private function root()
    {
        $userID = $this->User->id;
        return "user/$userID/files";
    }

    private function initUserRootStructure($userID)
    {
        $root = $this->userRoot($userID);

        $this->createDirStructure(
            $root,
            [
                'utils' => [
                    'other' => ['1.txt'],
                    'router.js',
                    'timer.js',
                    'unique'
                ],
                'src' => [
                    'utils' => [
                        'other' => [],
                        'router.js',
                        'timer.js',
                    ],
                    'App.vue',
                    'main.js',
                ],
                '.gitignore',
                'main.js',
                'timer.js',
                'tsconfig.json',
            ]
        );
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
            )
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'src')
                        ->where('mime', 'directory')
                        ->where('size', '3 items')
                        ->etc();
                });

                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json
                        ->where('name', 'utils')
                        ->where('mime', 'directory')
                        ->where('size', '4 items')
                        ->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
                    $json
                        ->where('name', '.gitignore')
                        ->where('mime', 'text/plain')
                        ->etc();
                });
                $json->has('fileInfos.3', function (AssertableJson $json) {
                    $json
                        ->where('name', 'main.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.4', function (AssertableJson $json) {
                    $json
                        ->where('name', 'timer.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.5', function (AssertableJson $json) {
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
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'src')
                        ->where('mime', 'directory')
                        ->where('size', '3 items')
                        ->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json
                        ->where('name', 'utils')
                        ->where('mime', 'directory')
                        ->where('size', '4 items')
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
        $dir = "{$this->root()}/unique_dir";
        $this->assertFalse($this->MainStorage->exists($dir));

        $res = $this->postJson("api/user/$userID/files/mkdir", ['filename' => 'unique_dir']);

        $this->assertTrue($this->MainStorage->exists($dir));
        $res->assertOk()
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
        $target = "{$this->root()}/tsconfig.json";

        $this->assertTrue($this->MainStorage->exists($target));

        $res = $this->deleteJson("api/user/$userID/files/remove", [
            'dir' => '/',
            'filenames' => ['src', 'tsconfig.json', 'notExistFile.txt', 'notExistDir'],
        ]);

        $this->assertFalse($this->MainStorage->exists($target));

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

        $origin = "{$this->root()}/tsconfig.json";

        $this->assertTrue($this->MainStorage->exists($origin));

        $res = $this->putJson("api/user/$userID/files/move", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['src', 'timer.js', 'notExist', 'tsconfig.json'],
            'options' => FileManager::OVERRIDE_NONE
        ]);

        $this->assertFalse($this->MainStorage->exists($origin));

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
        $origin = "{$this->root()}/timer.js";

        $this->assertTrue($this->MainStorage->exists($origin));

        $res = $this->putJson("api/user/$userID/files/move", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['timer.js'],
            'options' => FileManager::OVERRIDE_KEEPBOTH
        ]);

        $this->assertFalse($this->MainStorage->exists($origin));

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

        $dest = "{$this->root()}/src/utils/other";
        $origin = "{$this->root()}/utils/other";

        $this->assertTrue($this->MainStorage->exists($dest));
        $this->assertEquals(
            collect(
                $this->MainStorage->directories($dest)
            )->concat(
                collect(
                    $this->MainStorage->files($dest)
                )
            )->count(),
            0
        );

        $res = $this->putJson("api/user/$userID/files/move", [
            'fromDir' => '/utils',
            'toDir' => '/src/utils',
            'filenames' => ['other'],
            'options' => FileManager::OVERRIDE_REPLACE
        ]);

        $this->assertFalse($this->MainStorage->exists($origin));

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

    public function test_copy()
    {
        $userID = $this->User->id;
        $origin = "{$this->root()}/timer.js";
        $this->assertTrue($this->MainStorage->exists($origin));

        $res = $this->postJson("api/user/$userID/files/copy", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['src', 'timer.js', 'notExist', 'tsconfig.json'],
            'options' => FileManager::OVERRIDE_NONE
        ]);

        $this->assertTrue($this->MainStorage->exists($origin));

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

    public function test_copy_keepboth()
    {
        $userID = $this->User->id;
        $origin = "{$this->root()}/timer.js";
        $this->assertTrue($this->MainStorage->exists($origin));

        $res = $this->postJson("api/user/$userID/files/copy", [
            'fromDir' => '/',
            'toDir' => '/src/utils',
            'filenames' => ['timer.js'],
            'options' => FileManager::OVERRIDE_KEEPBOTH
        ]);

        $this->assertTrue($this->MainStorage->exists($origin));
        $this->assertTrue($this->MainStorage->exists($origin));
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

    public function test_copy_replace()
    {
        $userID = $this->User->id;

        $dest = "{$this->root()}/src/utils/other";
        $origin = "{$this->root()}/utils/other";

        $this->assertTrue($this->MainStorage->exists($origin));
        $this->assertTrue($this->MainStorage->exists($dest));
        $this->assertEquals(
            collect(
                $this->MainStorage->directories($dest)
            )->concat(
                collect(
                    $this->MainStorage->files($dest)
                )
            )->count(),
            0
        );

        $res = $this->postJson("api/user/$userID/files/copy", [
            'fromDir' => '/utils',
            'toDir' => '/src/utils',
            'filenames' => ['other'],
            'options' => FileManager::OVERRIDE_REPLACE
        ]);

        $this->assertTrue($this->MainStorage->exists($origin));
        $this->assertTrue($this->MainStorage->exists($dest));

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
