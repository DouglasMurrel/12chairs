<?php

namespace App\Controller;

use App\Service\TelegramService;
use App\Entity\CharacterOrder;
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
        private TelegramService $telegramSerice
    )
    {
    }
    
    #[Route(path: '/webhook', name: 'webhook')]
    public function webhook(Request $request): Response
    {
        $message = json_decode($request->getContent());
        $allowedChatIds = explode(',',$this->getParameter('telegram_chat_id'));
        $chatId = $message->message->chat->id;
        $allowed = in_array($chatId, $allowedChatIds);
        $text = $message->message->text;
        if ($text != "/abrakadabra" && !$allowed) {
            $resultText = 'You are not prepared!';
        } else if ($text == "/list") {
            $orders = $this->em->getRepository(CharacterOrder::class)->findBy([], ['id'=>'DESC']);
            $resultText = $this->render('telegram/order_list.html.twig', [
                'orders' => $orders
            ])->getContent();
        } else if ($text == "/abrakadabra") {
            $resultText = $chatId;
        } else if (preg_match('/\/order (\d+)/', $text, $m)) {
            $id = $m[1];
            $order = $this->em->getRepository(CharacterOrder::class)->find($id);
            if (!$order) {
                $resultText = 'Заявка с id=' . $id . ' не найдена';
            } else {
                $resultText = $this->render('telegram/order.html.twig', [
                            'order' => $order
                        ])->getContent();
            }
        } else {
            $resultText = $this->render('telegram/help.html.twig')->getContent();
        }
        
        $this->telegramSerice->sendMessage($resultText, $chatId);

        return new Response('OK');
    }
}
