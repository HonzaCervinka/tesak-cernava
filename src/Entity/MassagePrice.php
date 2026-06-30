<?php

namespace App\Entity;

use App\Repository\MassagePriceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MassagePriceRepository::class)]
class MassagePrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $minutes = 0;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: Massage::class, inversedBy: 'prices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Massage $massage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    public function setMinutes(int $minutes): static
    {
        $this->minutes = $minutes;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

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

    public function getMassage(): ?Massage
    {
        return $this->massage;
    }

    public function setMassage(?Massage $massage): static
    {
        $this->massage = $massage;

        return $this;
    }
}
