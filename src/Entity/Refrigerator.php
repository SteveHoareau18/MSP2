<?php

namespace App\Entity;

use App\Repository\RefrigeratorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefrigeratorRepository::class)]
class Refrigerator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $addingDate = null;

    #[ORM\ManyToOne(inversedBy: 'refrigerators')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FreshUser $owner = null;

    #[ORM\OneToMany(mappedBy: 'refrigerator', targetEntity: Food::class, cascade: ['remove'])]
    private Collection $foods;

    #[ORM\OneToMany(mappedBy: 'refrigerator', targetEntity: Alert::class)]
    private Collection $alerts;

    public function __construct()
    {
        $this->foods = new ArrayCollection();
        $this->alerts = new ArrayCollection();
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

    public function getAddingDate(): ?\DateTimeInterface
    {
        return $this->addingDate;
    }

    public function setAddingDate(?\DateTimeInterface $dateAdding): static
    {
        $this->addingDate = $dateAdding;

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
     * @return Collection<int, Food>
     */
    public function getFoods(): Collection
    {
        return $this->foods;
    }

    public function addFood(Food $food): static
    {
        if (!$this->foods->contains($food)) {
            $this->foods->add($food);
            $food->setRefrigerator($this);
        }

        return $this;
    }

    public function removeFood(Food $food): static
    {
        if ($this->foods->removeElement($food)) {
            // set the owning side to null (unless already changed)
            if ($food->getRefrigerator() === $this) {
                $food->setRefrigerator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setRefrigerator($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert)) {
            // set the owning side to null (unless already changed)
            if ($alert->getRefrigerator() === $this) {
                $alert->setRefrigerator(null);
            }
        }

        return $this;
    }
}
