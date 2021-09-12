<?php

namespace Tests\Feature;

use App\Helpers\PathHelper;
use Tests\TestCase;

class PathHelperTest extends TestCase
{

    public function test_extension()
    {
        $this->assertEquals('jpg', PathHelper::extension('/路徑/\\////\\指向/這邊.jpg'));
        $this->assertEquals('jpg', PathHelper::extension('/path/to/a b c d.jpg'));
    }

    public function test_dirname()
    {
        $this->assertEquals('/路徑/指向', PathHelper::dirname('/路徑/\\///\\/指向/這邊.jpg'));
        $this->assertEquals('/path/to', PathHelper::dirname('/path/to/a b c d.jpg'));
    }

    public function test_basename()
    {
        $this->assertEquals('這邊.的 檔案.jpg', PathHelper::basename('/路徑/指向/這邊.的 檔案.jpg'));
        $this->assertEquals('a+b-c_d.jpg', PathHelper::basename('/path//\\/to/a+b-c_d.jpg'));
    }

    public function test_root_file_name()
    {
        $this->assertEquals('路-徑', PathHelper::rootFileName('/路-徑/指向/這邊.的 檔案.jpg'));
        $this->assertEquals('path', PathHelper::rootFileName('/path/\\/\\\\/to/\\//a+b-c_d.jpg'));
        $this->assertEquals('dir', PathHelper::rootFileName('dir/file'));
        $this->assertEquals('dir', PathHelper::rootFileName('dir'));
    }

    public function test_format()
    {
        $this->assertEquals('/a/b/c/中文/a', PathHelper::format('\\a\\/b/\\/c////\\中文/\\//a'));
    }

    public function test_concat()
    {
        $this->assertEquals('/a/b/c/中文/a', PathHelper::concat('\\a\\/b/\\/', 'c///', '/\\/中文\\', '\\//a'));
    }

    public function test_equal()
    {
        $this->assertTrue(PathHelper::equal('\\a\\/b/\\/', '//\\a/b\\', '/a/\\b/'));
        $this->assertFalse(PathHelper::equal('\\a\\/b/\\/', '//\\a/b\\', 'a/\\b/'));
    }
}
