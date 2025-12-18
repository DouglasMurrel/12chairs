<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'characterOrder', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contacts = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $health = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $food = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $psychological = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $role = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $want = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $nowant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getContacts(): ?string
    {
        return $this->contacts;
    }

    public function setContacts(string $contacts): static
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function getHealth(): ?string
    {
        return $this->health;
    }

    public function setHealth(string $health): static
    {
        $this->health = $health;

        return $this;
    }

    public function getFood(): ?string
    {
        return $this->food;
    }

    public function setFood(string $food): static
    {
        $this->food = $food;

        return $this;
    }

    public function getPsychological(): ?string
    {
        return $this->psychological;
    }

    public function setPsychological(string $psychological): static
    {
        $this->psychological = $psychological;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getWant(): ?string
    {
        return $this->want;
    }

    public function setWant(string $want): static
    {
        $this->want = $want;

        return $this;
    }

    public function getNowant(): ?string
    {
        return $this->nowant;
    }

    public function setNowant(string $nowant): static
    {
        $this->nowant = $nowant;

        return $this;
    }
}
