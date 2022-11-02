# Laravel TG bot starter

Despite the package name, this package should work with Laravel > 5.2

## Installation - Laravel >= 5.2

Change project composer.json 
```json

    "require": {
       ...
        "yumir/tg-bot-laravel":"^1.2.8"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jura120596/tg-bot-laravel"
        }
    ],
```
Publish the config file and lib other files by running `php artisan vendor:publish --provider="Yumir\TgBotLaravel\ServiceProvider" --tag="all"`. 

Set your bot api settings in config/tgbot.php

Change config/app.php

Add alias
````php
        'TgBot' => Yumir\TgBotLaravel\TgBot::class,
````

## Usage

```php

TgBot::send(\TgBotApi\BotApiBase\Method\SendMessageMethod::create(config('tgbot.dev_id'), 'Hello'));

```



## Contact

Open an issue on GitHub if you have any problems or suggestions.


## License

The contents of this repository is released under the [MIT license](http://opensource.org/licenses/MIT).
