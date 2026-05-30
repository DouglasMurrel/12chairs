<?php

namespace App\Service;

use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
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
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('id', 3))
            ->andWhere(Criteria::expr()->neq('name', ''))
            ->orderBy(['id' => Criteria::ASC]);
        $orders = $this->em->getRepository(Order::class)->matching($criteria);
        $resultText = $this->twig->render('order_list.html.twig', [
            'orders' => $orders
        ]);
        if ($resultText == ''){
            $resultText = 'Заявок пока нет';
        }
        
        return $resultText;
    }
}
