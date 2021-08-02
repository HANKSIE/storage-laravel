<?php

namespace App\Library;

use Exception;
use \Illuminate\Support\Arr;

class Assert
{
    /**
     *
     * @param mixed $data
     * 
     * @throws Exception
     */
    public static function isArray($data)
    {
        if (!is_array($data)) {
            throw new Exception('data should be array');
        }
    }

    /**
     * 
     * @param string $key
     * @param mixed $data
     * 
     * @throws Exception
     */
    public static function ArrayHasKey($key, $data)
    {
        if (!isset($data[$key])) {
            throw new Exception("data has no key \"$key\".");
        }
    }

    /**
     * 參考 \Illuminate\Testing\AssertableJsonString::assertStructure()的實現
     * 
     * @param array $structure
     * @param array $data
     * 
     * @throws Exception
     */
    public static function arrayStructure($structure, $data)
    {
        //讓structure與data鍵值順序相同
        $structure = Arr::sort($structure);
        $data = Arr::sort($data);

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                self::isArray($data);
                foreach ($data as $d) {
                    self::arrayStructure($structure['*'], $d);
                }
            } else if (is_array($value)) {
                self::ArrayHasKey($key, $data);
                self::arrayStructure($structure[$key], $data[$key]);
            } else {
                self::ArrayHasKey($value, $data);
            }
        }
    }
}
