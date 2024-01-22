<?php

namespace App\Entity;

use App\Repository\FoodInRefrigeratorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoodInRefrigeratorRepository::class)]
class FoodRecipeInRefrigerator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, unique: false)]
    private ?Refrigerator $refrigerator = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Food $food = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $recipe = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefrigerator(): ?Refrigerator
    {
        return $this->refrigerator;
    }

    public function setRefrigerator(Refrigerator $refrigerator): static
    {
        $this->refrigerator = $refrigerator;

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

    public function getFood(): ?Food
    {
        return $this->food;
    }

    public function setFood(Food $food): static
    {
        $this->food = $food;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }
}
