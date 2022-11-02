<?php


namespace App\Bot;


/**
 * Trait StartCommand
 * @package App\Bot
 * @mixin \App\Bot\Handler
 */
trait StartCommand
{
    public function start() {
        TgBot::write($this->getRequest(), "Start command handled");
    }
}
