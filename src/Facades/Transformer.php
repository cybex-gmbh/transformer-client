<?php


namespace Foo\Bar\Example\Facades;

use Illuminate\Support\Facades\Facade;

class Transformer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Transformer';
    }
}