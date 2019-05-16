<?php

namespace Michael\Jobs\Serialize;

use Michael\Jobs\Interfaces\Serialize;

class PhpSerialize implements Serialize
{
    public function encode($data)
    {

        return serialize($data);
    }

    public function decode($data)
    {

        return unserialize($data);
    }
}