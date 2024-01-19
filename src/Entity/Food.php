<?php

namespace App\Entity;

use App\Repository\FoodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoodRepository::class)]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable: true)]
    private ?\DateTimeInterface $addingDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expireDate = null;

    #[ORM\ManyToOne(inversedBy: 'foods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Refrigerator $refrigerator = null;

    #[ORM\OneToMany(mappedBy: 'food', targetEntity: Alert::class, cascade: ['remove'])]
    private Collection $alerts;

    #[ORM\OneToMany(mappedBy: 'food', targetEntity: FoodRecipeInRefrigerator::class, cascade: ['remove'])]
    private Collection $foodRecipeInRefrigerator;


    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getAddingDate(): ?\DateTimeInterface
    {
        return $this->addingDate;
    }

    public function setAddingDate(\DateTimeInterface $addingDate): static
    {
        $this->addingDate = $addingDate;

        return $this;
    }

    public function getExpireDate(): ?\DateTimeInterface
    {
        return $this->expireDate;
    }

    public function setExpireDate(\DateTimeInterface $expireDate): static
    {
        $this->expireDate = $expireDate;

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
}
