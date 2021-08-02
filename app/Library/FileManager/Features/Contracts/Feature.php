<?php

namespace App\Library\FileManager\Features\Contracts;

use App\Library\FileManager\Helper;
use \Illuminate\Contracts\Filesystem\Filesystem;

abstract class Feature
{
    protected $Storage;
    protected $Helper;

    public function __construct(Filesystem $Storage, Helper $Helper)
    {
        $this->Storage = $Storage;
        $this->Helper = $Helper;
    }

    /**
     *
     * @param mixed $args
     * @return mixed
     */
    abstract public function __invoke(...$args);
}
