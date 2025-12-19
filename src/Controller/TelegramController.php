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
        if (property_exists($message,'message')){
            $chatId = $message->message->chat->id;
            $text = $message->message->text;
            $replyMarkup = null;
            $resultText = null;
            $user = $this->em->getRepository(User::class)->findOneBy(['chatId' => $chatId]);
            if ($text == "/start") {
                if ($user) {
                    $resultText = "Здравствуйте! Хотите изменить заявку?";
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->flush();
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
                    $resultText = <<<EOD
Здравствуйте! Хотите подать заявку?
Вам нужно будет заполнить поля:
- Имя
- Актуальные контакты для связи
- Желаемая роль или роли
- Во что вы хотите играть
- Во что вы НЕ хотите играть
- Пищевые ограничения
- Медицинские противопоказания и хронические болезни
- Психологические пожелания
- Прочее
EOD;
                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Начали!', 'callback_data' => 'start_order']
                            ],
                        ]
                    ];
                }
                $this->telegramService->sendMessage($chatId, $resultText, $replyMarkup);            
            } elseif ($user && $user->getState()=='enter_name') {
                $order = $user->getCharacterOrder();
                $order->setName($text);
                $user->setState('enter_contacts');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваши контакты для срочной связи (почта, ВК, телеграм и т.д.):');
            } elseif ($user && $user->getState()=='enter_contacts') {
                $order = $user->getCharacterOrder();
                $order->setContacts($text);
                $user->setState('enter_role');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Какую роль или роли вы хотели бы сыграть?');
            } elseif ($user && $user && $user->getState()=='enter_role') {
                $order = $user->getCharacterOrder();
                $order->setRole($text);
                $user->setState('enter_wants');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Во что вы хотели бы поиграть?');
            } elseif ($user && $user->getState()=='enter_wants') {
                $order = $user->getCharacterOrder();
                $order->setWant($text);
                $user->setState('enter_nowants');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'А во что вы НЕ хотите играть?');
            } elseif ($user && $user->getState()=='enter_nowants') {
                $order = $user->getCharacterOrder();
                $order->setNowant($text);
                $user->setState('enter_food');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваши пищевые ограничения:');
            } elseif ($user && $user->getState()=='enter_food') {
                $order = $user->getCharacterOrder();
                $order->setFood($text);
                $user->setState('enter_health');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваши хронические болезни, аллергии, медицинские противопоказания и т.д.');
            } elseif ($user && $user->getState()=='enter_health') {
                $order = $user->getCharacterOrder();
                $order->setHealth($text);
                $user->setState('enter_psychological');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Психологические противопооказания: что с вами ни в коем случае нельзя делать по жизни?');
            } elseif ($user && $user->getState()=='enter_psychological') {
                $order = $user->getCharacterOrder();
                $order->setPsychological($text);
                $user->setState('enter_other');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Что вы езе хотите сказать мастерам?');
            } elseif ($user && $user->getState()=='enter_other') {
                $order = $user->getCharacterOrder();
                $order->setOther($text);
                $user->setState('');
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Вот и все! Ваша заявка отправлена!?');
            }
        }
        if (property_exists($message,'callback_query')){
            $chatId = $message->callback_query->message->chat->id;
            $data = $message->callback_query->data;
            $user = $this->em->getRepository(User::class)->findOneBy(['chatId' => $chatId]);
            $replyMarkup = null;
            $resultText = null;
            if ($data=='start_order'){//начинаем подавать заявку
                $user->setState('enter_name');
                $order = new Order();
                $order
                        ->setUser($user)
                        ->setName('')
                        ->setContacts('')
                        ->setRole('')
                        ->setWant('')
                        ->setNowant('')
                        ->setFood('')
                        ->setHealth('')
                        ->setPsychological('')
                        ->setOther('')
                        ;
                $this->em->persist($user);
                $this->em->persist($order);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваше имя или ник:');
            }
        }

        return new Response('OK');
    }
}
