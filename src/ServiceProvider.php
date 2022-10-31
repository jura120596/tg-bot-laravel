<?php


namespace Yumir\TgBotLaravel;


use GuzzleHttp\Client;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton(Facade::class, function() {
            $botKey = config('services.tg.key');
            $client = new Client();
            $apiClient = new TgApiClient( $client);
            return new TgBot($botKey, $apiClient, new BotApiNormalizer());
        });
    }
}
