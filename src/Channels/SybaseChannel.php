<?php

namespace Yish\Notifications\Channels;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;

class SybaseChannel
{
    /**
     * @var Client
     */
    private $http;

    /**
     * @var array
     */
    private $config;

    public function __construct(Client $http, array $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed $notifiable
     * @param  \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('sybase', $notification)) {
            return;
        }

        $message = $notification->toSybase($notifiable);

        if ($this->isTaiwanMobile($to)) {
            $this->http->post($this->config['endpoint'], [
                'body' => $this->formatMessage(substr($to, 1), $message),
                'auth' => [$this->config['account'], $this->config['password']]
            ]);
        }
    }

    protected function formatMessage($to, $message)
    {
        $subject = mb_convert_encoding($message->subject, "big5", "UTF-8");
        $content = $txt = mb_convert_encoding($message->content, "big5", "UTF-8");

        return "Version=2.0
                    Subject={$subject}
                [MSISDN]
                    List=+886{$to}
                [MESSAGE]
                    Text={$content}
                [SETUP]
                    MobileNotification=YES
                    SplitText=YES
                    DCS=BIG5
                [END]";
    }

    public function isTaiwanMobile($to)
    {
        return starts_with($to, '0') && strlen($to) === 10;
    }
}

