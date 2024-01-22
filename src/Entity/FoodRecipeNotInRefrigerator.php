<?php

namespace App\Entity;

use App\Repository\FoodNotInRefrigeratorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoodNotInRefrigeratorRepository::class)]
class FoodRecipeNotInRefrigerator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    private ?Recipe $recipe = null;

    #[ORM\Column(nullable: true)]
    private ?bool $canBeRegroup = null;

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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

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

    public function isCanBeRegroup(): ?bool
    {
        return $this->canBeRegroup;
    }

    public function setCanBeRegroup(?bool $canBeRegroup): static
    {
        $this->canBeRegroup = $canBeRegroup;

        return $this;
    }
}
