<?php

namespace App\Service;

class OrderList {
    public function generateText(): string
    {
        // Здесь ваша логика генерации текста
        $data = [
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'user' => 'Пользователь',
            'content' => 'Секретный документ, сгенерированный автоматически.',
            'random_number' => random_int(1000, 9999)
        ];
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
