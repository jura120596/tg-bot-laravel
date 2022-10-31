<?php


namespace Yumir\TgBotLaravel;


use App\Http\Requests\Bot\BotCallbackRequest;
use Yumir\TgBotLaravel\HasAssocCallbackParams;
use Facades\Yumir\TgBotLaravel\TgBot;
use Illuminate\Support\Arr;
use TgBotApi\BotApiBase\Method\SendMessageMethod;
use TgBotApi\BotApiBase\Method\SetMyCommandsMethod;
use TgBotApi\BotApiBase\Type\BotCommandType;
use TgBotApi\BotApiBase\Type\InlineKeyboardButtonType;
use TgBotApi\BotApiBase\Type\KeyboardButtonType;
use TgBotApi\BotApiBase\Type\MessageEntityType;

class BaseHandler implements HasAssocCallbackParams
{

    const START = 'start';
    const COMMANDS = [
        self::START => 'Подписаться',
    ];
    const BACK_MESSAGES = [
        'Назад',
        'Back',
    ];
    /** @var BotCallbackRequest */
    private $req;

    public function __construct(BotCallbackRequest $req)
    {
        $this->req = $req;
    }

    private static function userCommands()
    {
        $r = [];
        foreach (Arr::only(self::COMMANDS, [
            self::START,
        ]) as $cmd => $label) {
            $r[] = BotCommandType::create($cmd, $label);
        }
        return $r;
    }

    public static function defineChatCommands()
    {
        TgBot::set(SetMyCommandsMethod::create([]));
        TgBot::set(SetMyCommandsMethod::create(self::userCommands()));
    }

    public function handle() {
        if($this->isCallback()) return;
        if (($message = array_search($this->req->getText(), self::BACK_MESSAGES)) !== false){
            $backKey = 'last_' . $this->req->getFrom()['id'];
            TgBot::deleteMsg($this->req);
            $message = setting($backKey, 'start');
            return $this->handleMethod($message);
        }
        if (($message = array_search($this->req->getText(), self::COMMANDS)) !== false){
            TgBot::deleteMsg($this->req);
            return $this->handleMethod($message);
        }
        $action = $this->req->getCommandEntity();
        if (is_null($action)) {
            $action = $this->getCachedCommand() ?: self::START;
        }
        if (method_exists($this, $action)){
            if ($this->req->isCommand()) TgBot::deleteMsg($this->req);
            return $this->handleMethod($action);
        }
        return self::notHandled($this->req);
    }

    /**
     * Проверяет наличие предобработчика команды с превиксом before (для start проверит beforeStart)
     * @param $m
     * @return bool
     */
    public function handleMethod($m) {
        $this->cacheCommand($m);
        $result =  false;
        if ($skip = method_exists($this, $b = 'before'. strtoupper(substr($m, 0 , 1)) . substr($m, 1))){
            $skip = $this->$b();
            $this->dump('handled ' . $b . ($skip ? ' with result for skip main action ' : ' without skip'));
        }
        if (!$skip) {
            $result = $this->$m();
            $this->dump('handled ' . $m . 'with result ' . $skip);
        }
        return $result;
    }

    public static function notHandled(BotCallbackRequest $request) {
        return TgBot::sendMessage(SendMessageMethod::create($request->getChat()['id'], "Это очень интересно!)"));
    }

    private function isCallback()
    {
        if (!$this->req->isCallback()) return false;
        $data = $this->req->getCallbackData();
        if (($forget = &$data[self::MSG_CLEAR])!=null) TgBot::deleteMsg($this->req);
        if (($clear = &$data[self::MSG_BTN_CLEAR])!=null) TgBot::changeMessageMarkup($this->req, []);
        $callback = &$data[self::BOT_ACTION_NAME];
        if (is_string($callback)) {
            if ($save = &$data[self::CACHE_ACTION]) $this->cacheCommand($callback);
            if (method_exists($this, $callback)) {
                $this->handleMethod($callback);
                return true;
            } else $this->dump('bad callback action name');
        } else $this->dump('callback not string');
        return false;
    }
    public function dump(...$vars) {
        if (config('app.env') === 'local') var_dump($vars);
    }

    /**
     * @return string
     */
    public function getHistoryKey(): string
    {
        $setKey = 'tg_history_' . $this->req->getFrom()['id'];
        return $setKey;
    }

    public function cacheCommand(string $command)
    {
        $setKey = $this->getHistoryKey();
        $back = explode(',',setting($setKey, '')) ?:[];
        if ($command !== $this->getCachedCommand()) {
            if (false != ($i = array_search($command, $back))) {
                $back = array_slice($back, 0, $i);
            }
            $back[] = $command;
            setting([$setKey => implode(',', $back)])->save();
        }
    }

    public function getCachedCommand() {

        $setKey = $this->getHistoryKey();
        $back = explode(',',setting($setKey, '')) ?:[];
        $last = null;
        if (($count = count($back)) > 0) $last = $back[$count-1];
        return $last;
    }

    /**
     * @param string $text
     * @param array|null $params
     * @param array|null $config
     * @return InlineKeyboardButtonType
     * @throws \TgBotApi\BotApiBase\Exception\BadArgumentException
     */
    public static function makeInlineBtn(string $text, ?array $params = null, ?array $config = null)
    {
        if ($params) $config['callbackData'] = http_build_query($params);
        $btn = InlineKeyboardButtonType::create($text, $config);
        return $btn;
    }

    /**
     * @param string $text
     * @param array|null $config
     * @return KeyboardButtonType
     * @throws \TgBotApi\BotApiBase\Exception\BadArgumentException
     */
    public function makeReplyBtn(string $text, ?array $config = null)
    {
        return KeyboardButtonType::create($text, $config);
    }

    public function getUrlEntity($offset = 0)
    {
        return [
            $u = config('app.url'),
            MessageEntityType::TYPE_URL,
            $offset,
            mb_strlen($u),
        ];
    }

}
