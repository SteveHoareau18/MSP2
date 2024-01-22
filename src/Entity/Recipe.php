<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastCookingDate = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FreshUser $owner = null;

    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: FoodRecipeNotInRefrigerator::class, cascade: ['remove'])]
    private Collection $foodRecipeNotInRefrigerators;

    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: FoodRecipeInRefrigerator::class, cascade: ['remove'])]
    private Collection $foodRecipeInRefrigerators;

    public function __construct()
    {
        $this->foodRecipeNotInRefrigerators = new ArrayCollection();
        $this->foodRecipeInRefrigerators = new ArrayCollection();
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

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): static
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getLastCookingDate(): ?\DateTimeInterface
    {
        return $this->lastCookingDate;
    }

    public function setLastCookingDate(\DateTimeInterface $lastCookingDate): static
    {
        $this->lastCookingDate = $lastCookingDate;

        return $this;
    }

    public function getOwner(): ?FreshUser
    {
        return $this->owner;
    }

    public function setOwner(?FreshUser $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
    /**
     * @return Collection<int, FoodRecipeNotInRefrigerator>
     */
    public function getFoodRecipeNotInRefrigerators(): Collection
    {
        return $this->foodRecipeNotInRefrigerators;
    }

    public function addFoodRecipeNotInRefrigerator(FoodRecipeNotInRefrigerator $foodNotInRefrigerator): static
    {
        if (!$this->foodRecipeNotInRefrigerators->contains($foodNotInRefrigerator)) {
            $this->foodRecipeNotInRefrigerators->add($foodNotInRefrigerator);
            $foodNotInRefrigerator->setRecipe($this);
        }

        return $this;
    }

    public function removeFoodRecipeNotInRefrigerator(FoodRecipeNotInRefrigerator $foodNotInRefrigerator): static
    {
        if ($this->foodRecipeNotInRefrigerators->removeElement($foodNotInRefrigerator)) {
            // set the owning side to null (unless already changed)
            if ($foodNotInRefrigerator->getRecipe() === $this) {
                $foodNotInRefrigerator->setRecipe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FoodRecipeInRefrigerator>
     */
    public function getFoodRecipeInRefrigerators(): Collection
    {
        return $this->foodRecipeInRefrigerators;
    }

    public function addFoodRecipeInRefrigerator(FoodRecipeInRefrigerator $foodRecipeInRefrigerator): static
    {
        if (!$this->foodRecipeInRefrigerators->contains($foodRecipeInRefrigerator)) {
            $this->foodRecipeInRefrigerators->add($foodRecipeInRefrigerator);
            $foodRecipeInRefrigerator->setRecipe($this);
        }

        return $this;
    }

    public function removeFoodRecipeInRefrigerator(FoodRecipeInRefrigerator $foodRecipeInRefrigerator): static
    {
        if ($this->foodRecipeInRefrigerators->removeElement($foodRecipeInRefrigerator)) {
            // set the owning side to null (unless already changed)
            if ($foodRecipeInRefrigerator->getRecipe() === $this) {
                $foodRecipeInRefrigerator->setRecipe(null);
            }
        }

        return $this;
    }
}
