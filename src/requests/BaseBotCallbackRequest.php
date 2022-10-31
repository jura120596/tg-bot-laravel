<?php

namespace Yumir\TgBotLaravel\Requests;

use App\Models\User;
use Yumir\TgBotLaravel\Handler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * Class BaseBotCallbackRequest
 * @package App\Http\Requests\Bot
 * @property mixed update_id
 * @property array message
 * @method getEntities()
 */
class BaseBotCallbackRequest extends FormRequest
{
    private $user = null;
    private $callbackData = [];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $data = $this->getFrom();
        $this->user = User::query()->where('tg_id', $data['id'])->first()
            ?: (new User())->forceFill(Arr::except($data, ['id']))->forceFill(['tg_id' => $data['id']]);
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function __call($method, $parameters)
    {
        if (strpos($method, 'get') !== 0) return parent::__call($method, $parameters);
        $method = str_replace('get', '', $method);
        $val = $this->getData();
        $key = strtolower($method);
        if (!isset($val[$key])) {
            return parent::__call($method, $parameters);
        }
        $val = &$val[$key];
        return $val;
    }

    public function getFrom()
    {
        return $this->getData()['from'];
    }

    public function getChat() {
        if ($this->isCallback()) return Arr::get(Arr::get($this->getData(), 'message',[]), 'chat', []);
        return Arr::get($this->getData(), 'chat');
    }
    public function isCommand() {
        return Arr::has($this->getData(), 'entities') ? Arr::first($this->getEntities(), function($item) {
            return $item['type'] === 'bot_command';
        }) : [];
    }
    public function isCallback() {
        return $this->input('callback_query', null) !== null || count($this->callbackData)>0;
    }

    public function replaceCallbackData($data) {
        $this->callbackData = $data;
    }
    public function getCallbackData() {
        if ($this->callbackData) return $this->callbackData;
        if ($this->isCallback()) parse_str($this->getData()['data'], $this->callbackData);
        return $this->callbackData;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->input('callback_query', ($this->input('message') ?: ($this->input('edited_message') ?: [])));

    }
    public function getMessage()
    {
        return Arr::get($this->input('callback_query', $this->input('edited_message', $this->input())), 'message');
    }
    public function getText() {
        return Arr::get($this->getMessage(), 'text');
    }


    public function getCommandEntity()
    {
        if ($this->isCallback()) {
            $data = $this->getData();
            $array = [];
            parse_str($data['data'], $array);
            return Arr::get($array, Handler::BOT_ACTION_NAME, null);
        }
        if ($cmd = $this->isCommand())
            return substr($this->getText(), $cmd['offset'] + 1, $cmd['length'] - 1);
        return null;
    }

    public function expectsJson()
    {
        return true;
    }

}
