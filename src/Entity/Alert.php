<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Food $food = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $alertedDate = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Refrigerator $refrigerator = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FreshUser $recipient = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFood(): ?Food
    {
        return $this->food;
    }

    public function setFood(?Food $food): static
    {
        $this->food = $food;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getAlertedDate(): ?\DateTimeInterface
    {
        return $this->alertedDate;
    }

    public function setAlertedDate(\DateTimeInterface $alertedDate): static
    {
        $this->alertedDate = $alertedDate;

        return $this;
    }

    public function getRefrigerator(): ?Refrigerator
    {
        return $this->refrigerator;
    }

    public function setRefrigerator(?Refrigerator $refrigerator): static
    {
        $this->refrigerator = $refrigerator;

        return $this;
    }

    public function getRecipient(): ?FreshUser
    {
        return $this->recipient;
    }

    public function setRecipient(?FreshUser $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }
}
