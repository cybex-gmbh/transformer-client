<?php


namespace Cybex\Transformer\Facades;

use Illuminate\Support\Facades\Facade;

class TransformerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Transformer';
    }
}