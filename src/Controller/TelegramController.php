<?php

namespace App\Controller;

use App\Service\TelegramService;
use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/telegram', name: 'telegram_')]
class TelegramController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private TelegramService $telegramService
    )
    {
    }
    
    #[Route(path: '/webhook', name: 'webhook')]
    public function webhook(Request $request): Response
    {
        $message = json_decode($request->getContent());
        $allowedChatIds = explode(',',$this->getParameter('telegram_chat_id'));
        $chatId = $message->message->chat->id;
        $text = $message->message->text;
        if ($text == "/start"){
            $resultText = "Вы записаны. Хотите подать заявку?";
        }
        
        $this->telegramService->sendMessage($chatId, $resultText);

        return new Response('OK');
    }
}
