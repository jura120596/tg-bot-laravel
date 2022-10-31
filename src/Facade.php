<?php


namespace Yumir\TgBotLaravel;


class Facade extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Yumir\TgBotLaravel\TgBot';
    }
}
