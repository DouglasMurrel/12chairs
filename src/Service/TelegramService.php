<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TelegramService {

    public function __construct(
        private HttpClientInterface $client,
        private ParameterBagInterface $parameterBag
    ) {
    }
    
    public function sendMessage($chatId, $message, $replyMarkup = null) {
        $token = $this->parameterBag->get('telegram_api_token');
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
        ];
        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }
        $this->client->request('POST', 'https://api.telegram.org/bot' . $token . '/sendMessage', ['json'=>$data]);
    }
}
