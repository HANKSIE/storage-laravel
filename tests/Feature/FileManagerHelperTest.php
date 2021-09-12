<?php

namespace Tests\Feature;

use App\Library\FileManager\Helper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileManagerHelperTest extends TestCase
{
    private $Helper;
    private $Storage;

    protected function setUp(): void
    {
        parent::setUp();
        $disk = config('filemanager.disk');
        Storage::fake($disk);
        $this->Storage = Storage::disk($disk);
        $this->Helper = $this->app->make(Helper::class);
        $this->buildDirStructure();
    }

    protected function buildDirStructure()
    {
        $this->Storage->makeDirectory('/dir');
        $file = UploadedFile::fake()->create('test.txt');
        $this->Storage->putFileAs('/dir', $file, 'test.txt');
        $file = UploadedFile::fake()->create('test copy.txt');
        $this->Storage->putFileAs('/dir', $file, 'test copy.txt');
    }

    public function test_is_directory()
    {
        $this->assertTrue($this->Helper->isDirectory('/dir'));
        $this->assertFalse($this->Helper->isDirectory('/dir/test.txt'));
    }

    public function test_fileInfo()
    {
        $res = $this->Helper->fileInfo(['/dir/test.txt'])[0];
        $this->assertArrayHasKey('name', $res);
        $this->assertArrayHasKey('mime', $res);
        $this->assertArrayHasKey('lastModified', $res);
        $this->assertArrayHasKey('size', $res);
    }

    public function test_create_unique_name()
    {
        $test = $this->Helper->createUniqueName('/dir/test.txt');
        $dir = $this->Helper->createUniqueName('/dir');

        $this->assertEquals('/dir/test copy copy.txt', $test);
        $this->assertEquals('/dir copy', $dir);
    }
}
