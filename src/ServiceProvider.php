<?php


namespace Yumir\TgBotLaravel;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use TgBotApi\BotApiBase\BotApiNormalizer;
use App\Bot\Handler;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton(Facade::class, function () {
            $botKey = config('services.tg.key');
            $client = new Client();
            $apiClient = new TgApiClient($client);
            return new TgBot($botKey, $apiClient, new BotApiNormalizer());
        });
        app()->singleton('Yumir\TgBotLaravel\Handler', function ($params) {
            return new Handler(...$params);
        });
    }

    public static function routes() {
        if (class_exists($name = '\App\Http\Controllers\Bot\BotController'))
            Route::post(config('tgbot.route_path'), $name . '@handleCallback')->name('botcmd');
    }
    public function boot()
    {
        self::routes();
        $this->commands([
            \Yumir\TgBotLaravel\Commands\StartCommand::class,
            \Yumir\TgBotLaravel\Commands\AddBotHandlerCommand::class,
        ]);
        $this->publishes($c = [
            __DIR__ . '/config/config.php' => config_path('tgbot.php')
        ], 'config');
        $this->publishes($m = [
            __DIR__ . '/migrations/2022_10_31_145433_add_users_tg_id_column.php' => database_path('migrations/' . date('Y_m_d_His') . '_add_users_tg_id_column.php')
        ], 'migrations');
        $this->publishes($cmd = [
            __DIR__ . '/commands/StartCommand.php' => app_path('Console/Commands/StartCommand.php'),
        ], 'commands');
        $this->publishes($req = [
            __DIR__ . '/requests/BotCallbackRequest.php' => app_path('Http/Requests/Bot/BotCallbackRequest.php')
        ], 'requests');
        $this->publishes($contr = [
            __DIR__ . '/controllers/BotController.php' => app_path('Http/Controllers/Bot/BotController.php')
        ], 'controllers');
        $this->publishes($h = [
            __DIR__ . '/Handler.php' => app_path('Bot/Handler.php'),
            __DIR__ . '/StartCommand.php' => app_path('Bot/StartCommand.php'),
        ], 'handler');
        $this->publishes(array_merge($c, $m, $req, $contr, $h), 'all');
    }
}
