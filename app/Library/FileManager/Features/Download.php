<?php

namespace App\Library\FileManager\Features;

use App\Helpers\PathHelper;
use App\Library\FileManager\Features\Contracts\Feature;
use App\Library\FileManager\Helper;

class Download extends Feature
{
    private $features = [];
    public function __construct(Helper $Helper, Zip $Zip)
    {
        parent::__construct($Helper);
        $this->features['Zip'] = $Zip;
    }

    public function __invoke(...$args)
    {
        list($dir, $filenames) = $args;
        $filenames = collect($filenames);

        $responseFilePath = '';
        $isTemp = false;

        if ($filenames->count() === 1) {
            $filePath = PathHelper::concat($dir, $filenames->first());

            if ($isTemp = $this->Helper->isDirectory($filePath)) {
                $responseFilePath = $this->features['Zip']($dir, $filenames);
            } else {
                $responseFilePath = $filePath;
            }
        } else {
            $responseFilePath = $this->features['Zip']($dir, $filenames);
            $isTemp = true;
        }

        $absoluteResponseFilePath = $this->Storage->path($responseFilePath);

        return [
            'absolutePath' => $absoluteResponseFilePath,
            'isTemp' => $isTemp
        ];
    }
}
