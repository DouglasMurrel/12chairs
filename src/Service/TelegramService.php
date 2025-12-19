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
    
    public function sendMessage($message, $chatId) {
        $token = $this->parameterBag->get('telegram_api_token');
        $this->client->request('POST', 'https://api.telegram.org/bot' . $token . '/sendMessage', [
            'json' => [
                'chat_id' => $chatId,
                'text' => $message,
            ]
        ]);
    }
}
