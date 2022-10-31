<?php


namespace Yumir\TgBotLaravel\Controllers;


use App\Http\Controllers\Controller;
use Yumir\TgBotLaravel\Requests\BaseBotCallbackRequest;
use Facades\Yumir\TgBotLaravel\TgBot;

class BaseBotController extends Controller
{

    public function handleCallback(BaseBotCallbackRequest $request)
    {
        try {
            app('Yumir\TgBotLaravel\Handler', [$request])->handle();
        } catch (\Throwable $e) {
            report($e);
            try {
                TgBot::write($request, "Ошибка cервера\n" . $e->getMessage() . "\n/start");
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

}
