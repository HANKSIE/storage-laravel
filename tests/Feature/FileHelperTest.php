<?php

namespace Tests\Feature;

use App\Helpers\FileHelper;
use Tests\TestCase;

class FileHelperTest extends TestCase
{
    public function test_format_bytes()
    {
        $this->assertEquals('0 B', FileHelper::formatBytes(0));
        $this->assertEquals('1 KB', FileHelper::formatBytes(1001));
        $this->assertEquals('123.5 KB', FileHelper::formatBytes(123456));
        $this->assertEquals('1 MB', FileHelper::formatBytes(1000000));
        $this->assertEquals('98.765432 MB', FileHelper::formatBytes(98765432, 10));
        $this->assertEquals('1 GB', FileHelper::formatBytes(1000000000));
        $this->assertEquals('164.93 GB', FileHelper::formatBytes(164925000000, 2));
        $this->assertEquals('1.1 TB', FileHelper::formatBytes(1111122222333));
        $this->assertEquals('748.7 TB', FileHelper::formatBytes(748711787819173));
    }
}
