<?php

namespace App\Service;

use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Order;

class OrderList {
    
    public function __construct(
        private EntityManagerInterface $em,
        private Environment $twig
    )
    {
    }
    
    public function generateText(): string
    {
            $orders = $this->em->getRepository(Order::class)->findBy([], ['id'=>'DESC']);
            $resultText = $this->render('order_list.html.twig', [
                'orders' => $orders
            ])->getContent();
            if ($resultText == ''){
                $resultText = 'Заявок пока нет';
            }
        
        return $resultText;
    }
}
