<?php

namespace App\Controller;

use App\Service\TelegramService;
use App\Entity\Order;
use App\Entity\User;
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
        $chatId = $message->message->chat->id;
        $text = $message->message->text;
        $replyMarkup = null;        
        if ($text == "/start"){
            $user = $this->em->getRepository(User::class)->findOneBy(['chatId'=>$chatId]);
            if ($user) {
                $resultText = "Здравствуйте! Хотите изменить заявку?";
            } else {
                $user = new User();
                $user
                        ->setChatId($chatId)
                        ->setName($message->message->chat->first_name . ' ' . $message->message->chat->last_name)
                        ->setUsername($message->message->chat->username)
                        ->setState('')
                        ;
                $this->em->persist($user);
                $this->em->flush();
                $resultText = "Здравствуйте! Хотите подать заявку?";
                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Начали!', 'callback_data' => 'start_order']
                        ],
                    ]
                ];
            }
        }
        
        $this->telegramService->sendMessage($chatId, $resultText, $replyMarkup);

        return new Response('OK');
    }
}
