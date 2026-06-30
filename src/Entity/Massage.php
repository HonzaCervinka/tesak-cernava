<?php

namespace App\Entity;

use App\Repository\MassageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MassageRepository::class)]
class Massage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private int $position = 0;

    /** @var Collection<int, MassagePrice> */
    #[ORM\OneToMany(targetEntity: MassagePrice::class, mappedBy: 'massage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['minutes' => 'ASC'])]
    private Collection $prices;

    public function __construct()
    {
        $this->prices = new ArrayCollection();
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /** @return Collection<int, MassagePrice> */
    public function getPrices(): Collection
    {
        return $this->prices;
    }

    public function addPrice(MassagePrice $price): static
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->setMassage($this);
        }

        return $this;
    }

    public function removePrice(MassagePrice $price): static
    {
        if ($this->prices->removeElement($price)) {
            if ($price->getMassage() === $this) {
                $price->setMassage(null);
            }
        }

        return $this;
    }
}
