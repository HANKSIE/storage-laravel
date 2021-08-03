<?php

namespace App\Library\FileManager\Features\Contracts;

use App\Library\FileManager\Helper;
use Illuminate\Support\Facades\Storage;

abstract class Feature
{
    protected $Storage;
    protected $Helper;

    public function __construct(Helper $Helper)
    {
        $this->Storage = Storage::disk(config('filemanager.disk'));
        $this->Helper = $Helper;
    }

    /**
     *
     * @param mixed $args
     * @return mixed
     */
    abstract public function __invoke(...$args);
}
