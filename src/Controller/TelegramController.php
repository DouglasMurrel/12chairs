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
        $masterChatIds = explode(',',$this->getParameter('telegram_chat_id'));
        
        $message = json_decode($request->getContent());
        $finalOrderMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => 'Показать заявку', 'callback_data' => 'show_order']
                ],
                [
                    ['text' => 'Изменить заявку', 'callback_data' => 'edit_order']
                ],
            ]
        ];
        if (property_exists($message,'message')){
            $chatId = $message->message->chat->id;
            if (property_exists($message->message, 'text')) {
                $text = $message->message->text;
                $replyMarkup = null;
                $resultText = null;
                /** @var User $user */
                $user = $this->em->getRepository(User::class)->findOneBy(['chatId' => $chatId]);
                if ($text == "/start") {
                    if ($user) {
                        $user->setState('');

                        if (!$user->getCharacterOrder()) {
                            $order = new Order();
                            $order
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
                            $this->em->persist($order);
                            $user->setCharacterOrder($order);
                        }

                        $this->em->persist($user);
                        $this->em->flush();
                        $this->telegramService->sendMessage($chatId, 'Здравствуйте! Хотите изменить заявку?', $finalOrderMarkup);
                    } else {
                        $user = new User();
                        if (property_exists($message->message->chat, 'first_name')){
                            $first_name = $message->message->chat->first_name;
                        } else {
                            $first_name = '';
                        }
                        if (property_exists($message->message->chat, 'last_name')){
                            $last_name = $message->message->chat->last_name;
                        } else {
                            $last_name = '';
                        }
                        $user
                                ->setChatId($chatId)
                                ->setName($first_name . ' ' . $last_name)
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
- Психологические противопоказания
- Прочее
EOD;
                        $replyMarkup = [
                            'inline_keyboard' => [
                                [
                                    ['text' => 'Начали!', 'callback_data' => 'start_order']
                                ],
                            ]
                        ];
                        $this->telegramService->sendMessage($chatId, $resultText, $replyMarkup);
                    }
                } elseif ($user && $user->getState() == 'enter_name') {
                    $order = $user->getCharacterOrder();
                    $order->setName($text);
                    $user->setState('enter_contacts');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваши контакты для срочной связи (почта, ВК, телеграм и т.д.)?');
                } elseif ($user && $user->getState() == 'enter_contacts') {
                    $order = $user->getCharacterOrder();
                    $order->setContacts($text);
                    $user->setState('enter_role');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Какую роль или роли вы хотели бы сыграть?');
                } elseif ($user && $user && $user->getState() == 'enter_role') {
                    $order = $user->getCharacterOrder();
                    $order->setRole($text);
                    $user->setState('enter_wants');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Во что вы хотели бы поиграть?');
                } elseif ($user && $user->getState() == 'enter_wants') {
                    $order = $user->getCharacterOrder();
                    $order->setWant($text);
                    $user->setState('enter_nowants');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'А во что вы НЕ хотите играть?');
                } elseif ($user && $user->getState() == 'enter_nowants') {
                    $order = $user->getCharacterOrder();
                    $order->setNowant($text);
                    $user->setState('enter_food');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваши пищевые ограничения?');
                } elseif ($user && $user->getState() == 'enter_food') {
                    $order = $user->getCharacterOrder();
                    $order->setFood($text);
                    $user->setState('enter_health');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваши хронические болезни, аллергии, медицинские противопоказания и т.д.?');
                } elseif ($user && $user->getState() == 'enter_health') {
                    $order = $user->getCharacterOrder();
                    $order->setHealth($text);
                    $user->setState('enter_psychological');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Психологические противопооказания: что с вами ни в коем случае нельзя делать по жизни?');
                } elseif ($user && $user->getState() == 'enter_psychological') {
                    $order = $user->getCharacterOrder();
                    $order->setPsychological($text);
                    $user->setState('enter_other');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Что вы еще хотите сказать мастерам?');
                } elseif ($user && $user->getState() == 'enter_other') {
                    $order = $user->getCharacterOrder();
                    $order->setOther($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Вот и все! Ваша заявка отправлена!', $finalOrderMarkup);

                    $message = $this->render('telegram/new_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_name') {
                    $order = $user->getCharacterOrder();
                    $order->setName($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_contacts') {
                    $order = $user->getCharacterOrder();
                    $order->setContacts($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user && $user->getState() == 'edit_role') {
                    $order = $user->getCharacterOrder();
                    $order->setRole($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_wants') {
                    $order = $user->getCharacterOrder();
                    $order->setWant($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_nowants') {
                    $order = $user->getCharacterOrder();
                    $order->setNowant($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_food') {
                    $order = $user->getCharacterOrder();
                    $order->setFood($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_health') {
                    $order = $user->getCharacterOrder();
                    $order->setHealth($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_psychological') {
                    $order = $user->getCharacterOrder();
                    $order->setPsychological($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                } elseif ($user && $user->getState() == 'edit_other') {
                    $order = $user->getCharacterOrder();
                    $order->setOther($text);
                    $user->setState('');
                    $this->em->persist($user);
                    $this->em->persist($order);
                    $this->em->flush();
                    $this->telegramService->sendMessage($chatId, 'Ваша заявка сохранена!', $finalOrderMarkup);

                    $message = $this->render('telegram/edit_order.html.twig', [
                                'order' => $order
                            ])->getContent();
                    foreach ($masterChatIds as $masterChatId) {
                        $this->telegramService->sendMessageMaster($masterChatId, $message);
                    }
                }
            }
        }
        if (property_exists($message,'callback_query')){
            $chatId = $message->callback_query->message->chat->id;
            $data = $message->callback_query->data;
            $user = $this->em->getRepository(User::class)->findOneBy(['chatId' => $chatId]);
            $replyMarkup = null;
            $resultText = null;
            $cancelReplyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Отменить', 'callback_data' => 'cancel'],
                    ],
                ]
            ];
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
                $this->telegramService->sendMessage($chatId, 'Ваше имя/ник?');
            } elseif ($data=='show_order'){
                $order = $user->getCharacterOrder();
                $orderText = "Ваша заявка:\n". $this->render('telegram/order.html.twig', [
                            'order' => $order
                        ])->getContent();
                $this->telegramService->sendMessage($chatId, $orderText, $finalOrderMarkup);
            } elseif ($data=='edit_order'){//начинаем редактировать заявку) 
                $order = $user->getCharacterOrder();
                $orderText = "Ваша заявка:\n". $this->render('telegram/order.html.twig', [
                            'order' => $order
                        ])->getContent();
                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Имя', 'callback_data' => 'edit_name'],
                            ['text' => 'Контакты', 'callback_data' => 'edit_contacts'],
                            ['text' => 'Роль', 'callback_data' => 'edit_role'],
                        ],[
                            ['text' => 'Чего хочет', 'callback_data' => 'edit_want'],
                            ['text' => 'Чего не хочет', 'callback_data' => 'edit_nowant'],
                            ['text' => 'Еда', 'callback_data' => 'edit_food'],
                        ],[
                            ['text' => 'Медицина', 'callback_data' => 'edit_health'],
                            ['text' => 'Психология', 'callback_data' => 'edit_psychological'],
                            ['text' => 'Дополнение', 'callback_data' => 'edit_other'],
                        ],
                    ]
                ];
                $this->telegramService->sendMessage($chatId, $orderText, $replyMarkup);
            } elseif ($data=='edit_name'){
                $user->setState('edit_name');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваше имя/ник?', $cancelReplyMarkup);
            } elseif ($data=='edit_contacts'){
                $user->setState('edit_contacts');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваши контакты для срочной связи (почта, ВК, телеграм и т.д.)?', $cancelReplyMarkup);
            } elseif ($data=='edit_role'){
                $user->setState('edit_role');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Какую роль или роли вы хотели бы сыграть?', $cancelReplyMarkup);
            } elseif ($data=='edit_want'){
                $user->setState('edit_want');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Во что вы хотели бы поиграть?', $cancelReplyMarkup);
            } elseif ($data=='edit_nowant'){
                $user->setState('edit_nowant');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'А во что вы НЕ хотите играть?', $cancelReplyMarkup);
            } elseif ($data=='edit_food'){
                $user->setState('edit_food');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваши пищевые ограничения?', $cancelReplyMarkup);
            } elseif ($data=='edit_health'){
                $user->setState('edit_health');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Ваши хронические болезни, аллергии, медицинские противопоказания и т.д.?', $cancelReplyMarkup);
            } elseif ($data=='edit_psychological'){
                $user->setState('edit_psychological');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Психологические противопооказания: что с вами ни в коем случае нельзя делать по жизни?', $cancelReplyMarkup);
            } elseif ($data=='edit_other'){
                $user->setState('edit_other');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Что вы еще хотите сказать мастерам?', $cancelReplyMarkup);
            } elseif ($data=='cancel'){
                $user->setState('');
                $this->em->persist($user);
                $this->em->flush();
                $this->telegramService->sendMessage($chatId, 'Изменение отменено!', $finalOrderMarkup);
            }
        }

        return new Response('OK');
    }
    
    #[Route(path: '/master_webhook', name: 'master_webhook')]
    public function masterWebhook(Request $request): Response
    {
        $message = json_decode($request->getContent());
        $allowedChatIds = explode(',',$this->getParameter('telegram_chat_id'));
        $chatId = $message->message->chat->id;
        $allowed = in_array($chatId, $allowedChatIds);
        $text = $message->message->text;
        if ($text != "/abrakadabra" && !$allowed) {
            $resultText = 'You are not prepared!';
        } else if ($text == "/list") {
            $orders = $this->em->getRepository(Order::class)->findBy([], ['id'=>'DESC']);
            $resultText = $this->render('telegram/order_list.html.twig', [
                'orders' => $orders
            ])->getContent();
            if ($resultText == ''){
                $resultText = 'Заявок пока нет';
            }
        } else if ($text == "/abrakadabra") {
            $resultText = $chatId;
        } else if (preg_match('/\/order (\d+)/', $text, $m)) {
            $id = $m[1];
            $order = $this->em->getRepository(Order::class)->find($id);
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
        
        $this->telegramService->sendMessageMaster($chatId, $resultText);

        return new Response('OK');
    }
}
