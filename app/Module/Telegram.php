<?php

namespace App\Module;

use App\Tasks\IhttpTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class Telegram
{
    protected $to = '';
    protected $text = '';
    protected $token = '';

    public static function create($token = null): self
    {
        return new self($token);
    }

    public function __construct($token = null)
    {
        $this->token = $token ?: Base::settingFind("notifyConfig", "telegram_token");
    }

    /**
     * @param string $to
     * @return $this
     */
    public function setTo(string $to): self
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * 发送消息
     * @return array
     */
    public function send()
    {
        if (empty($this->token)) {
            return Base::retError("token is null");
        }
        //
        return Ihttp::ihttp_post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $this->to,
            'text' => $this->text,
        ]);
    }

    /**
     * 发送消息 异步
     */
    public function sendAsync()
    {
        if (empty($this->token)) {
            return;
        }
        //
        $task = new IhttpTask("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $this->to,
            'text' => $this->text,
        ]);
        Task::deliver($task);
    }

    /**
     * 设置 Webhook
     * @return array
     */
    public function setWebhook()
    {
        if (empty($this->token)) {
            return Base::retError("token is null");
        }
        //
        $token = Base::generatePassword(16);
        Base::setting("notifyConfig", [ 'telegram_webhook_token' => $token ], true);
        //
        return Ihttp::ihttp_post("https://api.telegram.org/bot{$this->token}/setWebhook", [
            'url' => url('telegram/webhook?token=' .  $token)
        ]);
    }

    /**
     * 设置 Webhook 异步
     */
    public function setWebhookAsync()
    {
        if (empty($this->token)) {
            return;
        }
        //
        $token = Base::generatePassword(16);
        Base::setting("notifyConfig", [ 'telegram_webhook_token' => $token ], true);
        //
        $task = new IhttpTask("https://api.telegram.org/bot{$this->token}/setWebhook", [
            'url' => url('telegram/webhook?token=' . $token)
        ]);
        Task::deliver($task);
    }

    /**
     * 删除 Webhook
     * @return array
     */
    public function deleteWebhook()
    {
        if (empty($this->token)) {
            return Base::retError("token is null");
        }
        return Ihttp::ihttp_post("https://api.telegram.org/bot{$this->token}/deleteWebhook", []);
    }

    /**
     * 获取 Webhook
     * @return array
     */
    public function getWebhookInfo()
    {
        if (empty($this->token)) {
            return Base::retError("token is null");
        }
        return Ihttp::ihttp_post("https://api.telegram.org/bot{$this->token}/getWebhookInfo", []);
    }
}
