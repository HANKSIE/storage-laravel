<?php

namespace App\Contracts;

use App\Library\Assert;
use ArrayAccess;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * key-value pair 結構約束
 */
abstract class Payload implements Jsonable, Arrayable, ArrayAccess
{
    public $payload = [];

    /**
     * define payload structure
     *
     * @return array
     */
    abstract public static function structure();

    /**
     *
     * @param array $payload
     */
    public function __construct($payload)
    {
        $structure = $this->structure();
        if (empty($structure)) {
            throw new Exception('structure() return array cannot be empty.');
        }
        Assert::arrayStructure($structure, $payload);
        $this->payload = $payload;
    }

    public static function make($payload)
    {
        return new static($payload);
    }

    public function toArray()
    {
        return $this->payload;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->payload);
    }

    public function offsetSet($offset, $value)
    {
        throw new Exception('Payload can only be read after created');
    }

    public function offsetExists($offset)
    {
        return isset($this->payload[$offset]);
    }

    public function offsetUnset($offset)
    {
        throw new Exception('Payload can only be read after created');
    }

    public function offsetGet($offset)
    {
        return isset($this->payload[$offset]) ? $this->payload[$offset] : null;
    }
}
