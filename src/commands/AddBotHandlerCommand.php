<?php
namespace Yumir\TgBotLaravel\Commands;

use Yumir\TgBotLaravel\TgBot;
use Illuminate\Console\Command;

class AddBotHandlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:bot_handler {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create tgbot handler trait for command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        if (!$name & !($name = $this->ask('Please enter trait class name. (blank for exit)'))) {
            return;
        }
        $code = <<<PHP
<?php
namespace  App\Bot;


trait $name {

}
PHP;
        file_put_contents(app_path('Bot/'.$name.'.php'), $code);
        $file = app_path('Bot/Handler.php');
        $code = file_get_contents($file);
        foreach ($lines = explode("\n", $code) as $i => $line) {
            if ($line === '{') {
                $lines[$i] .= "\n\tuse ".$name.';';
                break;
             }
        }
        file_put_contents($file, implode("\n", $lines));
    }
}
