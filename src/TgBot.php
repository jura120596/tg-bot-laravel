<?php


namespace Yumir\TgBotLaravel;


use TgBotApi\BotApiBase\BotApiComplete;
use TgBotApi\BotApiBase\Method\DeleteMessageMethod;
use TgBotApi\BotApiBase\Method\EditMessageReplyMarkupMethod;
use TgBotApi\BotApiBase\Method\SendInvoiceMethod;
use TgBotApi\BotApiBase\Method\SendMessageMethod;
use TgBotApi\BotApiBase\Method\SetWebhookMethod;
use TgBotApi\BotApiBase\Type\InlineKeyboardMarkupType;
use TgBotApi\BotApiBase\Type\MessageType;
use TgBotApi\BotApiBase\Type\ReplyKeyboardMarkupType;
use App\Http\Requests\Bot\BotCallbackRequest;

class TgBot extends BotApiComplete
{
    public static function boot()
    {
        \Facades\Yumir\TgBotLaravel\TgBot::set(SetWebhookMethod::create(config('tgbot.whurl') . route('botcmd', [], false)));
        Handler::defineChatCommands();
        \Facades\Yumir\TgBotLaravel\TgBot::send(\TgBotApi\BotApiBase\Method\SendMessageMethod::create(config('tgbot.dev_id'), 'Config updated'));
    }
    /**
     * @param BotCallbackRequest $r
     * @param string $message
     * @param array|null $data
     */
    public static function write(BotCallbackRequest $r, string $message, array $data = null): MessageType
    {
        return \Facades\Yumir\TgBotLaravel\TgBot::sendMessage(SendMessageMethod::create($r->getUser()->tg_id ?: $r->getChat()['id'], $message, $data));
    }

    public static function changeMessageMarkup(BotCallbackRequest $r, array $keyboard, bool $inline = true)
    {
        if ($r->isCallback() && $r->getMessage()) {
            return \Facades\Yumir\TgBotLaravel\TgBot::editMessageReplyMarkup(EditMessageReplyMarkupMethod::create($r->getChat()['id'], $r->getMessage()['message_id'], [
                'replyMarkup' => $inline ? InlineKeyboardMarkupType::create($keyboard): ReplyKeyboardMarkupType::create($keyboard)
            ]));
        }
    }

    public static function deleteMsg(BotCallbackRequest $request)
    {
        \Facades\Yumir\TgBotLaravel\TgBot::delete(DeleteMessageMethod::create($request->getChat()['id'], $request->getMessage()['message_id']));
    }

    public static function sendInvoiceMessage(BotCallbackRequest $request,
                                              $title,
                                              $description,
                                              $payload,
                                              $amount,
                                              array $data = null)
    {
        return \Facades\Yumir\TgBotLaravel\TgBot::send(SendInvoiceMethod::create(
            $request->getChat()['id'],
            $title,
            $description,
            $payload ?: 'any',
            config('tgbot.pay_token', ''),
            config('tgbot.start', ''),
            "RUB",
            [
                [
                    'label' => 'Руб',
                    'amount' => $amount * 100,
                ]
            ]
        ));
    }
}
