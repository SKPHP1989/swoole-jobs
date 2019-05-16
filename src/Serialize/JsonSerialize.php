<?php

namespace Michael\Jobs\Serialize;

use Michael\Jobs\Interfaces\Serialize;

class JsonSerialize implements Serialize
{
    public function encode($data)
    {

        return json_encode($data);
    }

    public function decode($data)
    {

        return json_decode($data, true);
    }
}