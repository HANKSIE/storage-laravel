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

        $this->actingAs($this->User);
    }

    private function userRoot($userID)
    {
        return "user/$userID/files";
    }

    private function initUserRoot($userID)
    {
        $root = $this->userRoot($userID);

        $gitignoreFile = UploadedFile::fake()->create('.gitignore');
        $tsConfigFile = UploadedFile::fake()->create('tsconfig.json');
        $this->MainStorage->putFileAs($root, $gitignoreFile, '.gitignore');
        $this->MainStorage->putFileAs($root, $tsConfigFile, 'tsconfig.json');

        $srcDir = "$root/src";
        $this->MainStorage->makeDirectory($srcDir);
        $mainTsFile = UploadedFile::fake()->create('main.js');
        $appVueFile = UploadedFile::fake()->create('App.vue');
        $this->MainStorage->putFileAs($srcDir, $mainTsFile, 'main.js');
        $this->MainStorage->putFileAs($srcDir, $appVueFile, 'App.vue');

        $utilsDir = "$srcDir/utils";
        $this->MainStorage->makeDirectory($utilsDir);
        $timerTsFile = UploadedFile::fake()->create('timer.js');
        $routerTsFile = UploadedFile::fake()->create('router.js');

        $this->MainStorage->putFileAs($utilsDir, $timerTsFile, 'timer.js');
        $this->MainStorage->putFileAs($utilsDir, $routerTsFile, 'router.js');

        /*
        directories structure:

        user/{uid}/
                |
                ----- src/
                        |
                        ----- main.js
                        |
                        ----- App.js
                        |
                        ----- utils/
                                    |
                                    ----- timer.js
                                    |
                                    ----- router.js
                |
                ----- .gitignore
                |
                ----- tsconfig.json
        */
    }

    public function test_list_all()
    {

        $userID = $this->User->id;
        $this->initUserRoot($userID);

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
            )->assertJsonCount(3, 'fileInfos')
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
                        ->where('name', '.gitignore')
                        ->where('mime', 'text/plain')
                        ->etc();
                });
                $json->has('fileInfos.2', function (AssertableJson $json) {
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
        $this->initUserRoot($userID);

        $res = $this->postJson("api/user/$userID/files", [
            'dir' => '/src',
            'options' => FileManager::LIST_DIR_ONLY
        ]);

        $res->assertOk()
            ->assertJsonCount(1, 'fileInfos')
            ->assertJson(function (AssertableJson $json) {
                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'utils')
                        ->where('mime', 'directory')
                        ->where('size', '2 items')
                        ->etc();
                });
            });
    }

    public function test_list_file_only()
    {

        $userID = $this->User->id;
        $this->initUserRoot($userID);

        $res = $this->postJson("api/user/$userID/files", [
            'dir' => '/src/utils',
            'options' => FileManager::LIST_FILE_ONLY
        ]);

        $res->assertOk()
            ->assertJsonCount(2, 'fileInfos')
            ->assertJson(function (AssertableJson $json) {

                $json->has('fileInfos.0', function (AssertableJson $json) {
                    $json
                        ->where('name', 'router.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
                $json->has('fileInfos.1', function (AssertableJson $json) {
                    $json
                        ->where('name', 'timer.js')
                        ->where('mime', 'application/javascript')
                        ->etc();
                });
            });
    }
}
