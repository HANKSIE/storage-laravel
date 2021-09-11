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
                    $json->whereAll([
                        'name' => 'src',
                        'mime' => 'directory',
                        'size' => '3 items'
                    ])->etc();
                });

                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'utils',
                        'mime' => 'directory',
                        'size' => '4 items'
                    ])->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => '.gitignore',
                        'mime' => 'text/plain'
                    ])->etc();
                });
                $json->has('fileInfos.3', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'main.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                });
                $json->has('fileInfos.4', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'timer.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                });
                $json->has('fileInfos.5', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'tsconfig.json',
                        'mime' => 'application/json'
                    ])->etc();
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
                    $json->whereAll([
                        'name' => 'src',
                        'mime' => 'directory',
                        'size' => '3 items'
                    ])->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'utils',
                        'mime' => 'directory',
                        'size' => '4 items'
                    ])->etc();
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
                    $json->whereAll([
                        'name' => '.gitignore',
                        'mime' => 'text/plain'
                    ])->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'main.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'timer.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                });
                $json->has('fileInfos.3', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'tsconfig.json',
                        'mime' => 'application/json'
                    ])->etc();
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
                $json->whereAll([
                    'exist' => false,
                    'isSuccess' => true
                ])->has('fileInfo', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'unique_dir',
                        'mime' => 'directory'
                    ])->etc();
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
                    $json->whereAll([
                        'name' => 'tsconfig.json',
                        'mime' => 'application/json'
                    ])->etc();
                })->whereAll([
                    'exists' => ['timer.js'],
                    'notExists' => ['notExist'],
                    'selfs' => ['src']
                ]);
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
                    $json->whereAll([
                        'name' => 'timer copy.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                })->whereAll([
                    'exists' => ['timer.js'],
                    'notExists' => [],
                    'selfs' => []
                ]);
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
                    $json->whereAll([
                        'name' => 'other',
                        'size' => '1 items'
                    ])->etc();
                })->whereAll([
                    'exists' => ['other'],
                    'notExists' => [],
                    'selfs' => []
                ]);
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
                    $json->whereAll([
                        'name' => 'tsconfig.json',
                        'mime' => 'application/json'
                    ])->etc();
                })->whereAll([
                    'exists' => ['timer.js'],
                    'notExists' => ['notExist'],
                    'selfs' => ['src']
                ]);
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
                    $json->whereAll([
                        'name' => 'timer copy.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                })->whereAll([
                    'exists' => ['timer.js'],
                    'notExists' => [],
                    'selfs' => []
                ]);
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
                    $json->whereAll([
                        'name' => 'other',
                        'size' => '1 items'
                    ])->etc();
                })->whereAll([
                    'exists' => ['other'],
                    'notExists' => [],
                    'selfs' => []
                ]);
            });
    }

    public function test_rename()
    {
        $userID = $this->User->id;

        $origin = "{$this->root()}/utils";
        $this->assertTrue($this->MainStorage->exists($origin));

        $res = $this->putJson("api/user/$userID/files/rename", [
            'dir' => '/',
            'oldFileName' => 'utils',
            'newFileName' => 'unique'
        ]);

        $this->assertFalse($this->MainStorage->exists($origin));

        $res->assertJson(function (AssertableJson $json) {
            $json->whereAll([
                'exist' => false,
                'isSuccess' => true
            ])->has('fileInfo', function (AssertableJson $json) {
                $json->whereAll([
                    'name' => 'unique',
                    'mime' => 'directory',
                    'size' => '4 items'
                ])->etc();
            });
        });
    }

    public function test_rename_repeat()
    {
        $userID = $this->User->id;

        $origin = "{$this->root()}/utils";
        $this->assertTrue($this->MainStorage->exists($origin));

        $res = $this->putJson("api/user/$userID/files/rename", [
            'dir' => '/',
            'oldFileName' => 'utils',
            'newFileName' => 'src'
        ]);

        $this->assertTrue($this->MainStorage->exists($origin));
        $this->assertTrue($this->MainStorage->exists($origin));

        $res->assertExactJson([
            'exist' => true,
            'isSuccess' => false,
            'fileInfo' => null
        ]);
    }

    public function test_download()
    {
        $userID = $this->User->id;

        $res = $this->postJson("api/user/$userID/files/download", [
            'dir' => '/',
            'filenames' => ['src', 'utils', 'tsconfig.json'],
        ]);

        $res->assertDownload();

        //TODO Assert whether zip or file path exists
    }

    public function test_upload()
    {
        $files = [
            '/src/a.txt' => UploadedFile::fake()->create('a.txt'),
            '/src/b.txt' => UploadedFile::fake()->create('b.txt'),
            '/other/test.jpg' => UploadedFile::fake()->create('test.jpg'),
            '/timer.js' => UploadedFile::fake()->create('timer.js'),
            '/index.html' => UploadedFile::fake()->create('index.html'),
        ];

        $userID = $this->User->id;

        $uploadSuccessFilepaths = ["{$this->root()}/utils/src", "{$this->root()}/utils/index.html"];

        foreach ($uploadSuccessFilepaths as $path) {
            $this->assertFalse($this->MainStorage->exists($path));
        }

        $res = $this->postJson(
            "api/user/$userID/files/upload",
            array_merge(
                [
                    'dir' => '/utils',
                    'options' => FileManager::OVERRIDE_NONE,
                ],
                $files
            )
        );

        $res->assertJson(function (AssertableJson $json) {
            $json->whereAll([
                'fails' => [],
                'exists' => ['other', 'timer.js']
            ])->has('fileInfos.0', function (AssertableJson $json) {
                $json->whereAll([
                    'name' => 'src',
                    'mime' => 'directory',
                    'size' => '2 items'
                ])->etc();
            })
                ->has('fileInfos.1', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'index.html',
                        'mime' => 'text/html'
                    ])->etc();
                });
        });

        foreach ($uploadSuccessFilepaths as $path) {
            $this->assertTrue($this->MainStorage->exists($path));
        }
    }

    public function test_upload_keepboth()
    {
        $files = [
            '/other/test.jpg' => UploadedFile::fake()->create('test.jpg'),
            '/timer.js' => UploadedFile::fake()->create('timer.js'),
            '/index.html' => UploadedFile::fake()->create('index.html'),
        ];

        $userID = $this->User->id;

        $uploadSuccessFilepaths = [
            "{$this->root()}/utils/other copy",
            "{$this->root()}/utils/index.html",
            "{$this->root()}/utils/timer copy.js",
        ];

        foreach ($uploadSuccessFilepaths as $path) {
            $this->assertFalse($this->MainStorage->exists($path));
        }

        $res = $this->postJson(
            "api/user/$userID/files/upload",
            array_merge(
                [
                    'dir' => '/utils',
                    'options' => FileManager::OVERRIDE_KEEPBOTH,
                ],
                $files
            )
        );

        $res->assertJson(function (AssertableJson $json) {
            $json->whereAll([
                'fails' => [],
                'exists' => ['other', 'timer.js']
            ])->has('fileInfos.0', function (AssertableJson $json) {
                $json->whereAll([
                    'name' => 'other copy',
                    'mime' => 'directory',
                    'size' => '1 items'
                ])->etc();
            })
                ->has('fileInfos.1', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'timer copy.js',
                        'mime' => 'application/javascript'
                    ])->etc();
                })
                ->has('fileInfos.2', function (AssertableJson $json) {
                    $json->whereAll([
                        'name' => 'index.html',
                        'mime' => 'text/html'
                    ])->etc();
                });
        });

        foreach ($uploadSuccessFilepaths as $path) {
            $this->assertTrue($this->MainStorage->exists($path));
        }
    }

    public function test_upload_replace()
    {
        $files = [
            '/other/1.jpg' => UploadedFile::fake()->create('1.jpg'),
            '/other/2.jpg' => UploadedFile::fake()->create('2.jpg'),
            '/other/3.jpg' => UploadedFile::fake()->create('3.jpg'),
            '/other/4.jpg' => UploadedFile::fake()->create('4.jpg'),
        ];

        $userID = $this->User->id;

        $res = $this->postJson(
            "api/user/$userID/files/upload",
            array_merge(
                [
                    'dir' => '/utils',
                    'options' => FileManager::OVERRIDE_REPLACE,
                ],
                $files
            )
        );

        $res->assertJson(function (AssertableJson $json) {
            $json->whereAll([
                'fails' => [],
                'exists' => ['other']
            ])->has('fileInfos.0', function (AssertableJson $json) {
                $json->whereAll([
                    'name' => 'other',
                    'mime' => 'directory',
                    'size' => '4 items'
                ])->etc();
            });
        });
    }
}
