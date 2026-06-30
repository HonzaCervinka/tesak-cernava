<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $features = [];

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\Column]
    private bool $priceFrom = false;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $priceUnit = null;

    #[ORM\Column(nullable: true)]
    private ?int $priceSingleNight = null;

    #[ORM\Column]
    private int $capacity = 0;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $showOnHomepage = false;

    /** @var Collection<int, Image> */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'room', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'room')]
    private Collection $reservations;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /** @return list<string> */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /** @param list<string> $features */
    public function setFeatures(array $features): static
    {
        $this->features = array_values($features);

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isPriceFrom(): bool
    {
        return $this->priceFrom;
    }

    public function setPriceFrom(bool $priceFrom): static
    {
        $this->priceFrom = $priceFrom;

        return $this;
    }

    public function getPriceUnit(): ?string
    {
        return $this->priceUnit;
    }

    public function setPriceUnit(?string $priceUnit): static
    {
        $this->priceUnit = $priceUnit;

        return $this;
    }

    public function getPriceSingleNight(): ?int
    {
        return $this->priceSingleNight;
    }

    public function setPriceSingleNight(?int $priceSingleNight): static
    {
        $this->priceSingleNight = $priceSingleNight;

        return $this;
    }

    /** True when pricing is per person (unit mentions "osoba"). */
    public function isPerPerson(): bool
    {
        return null !== $this->priceUnit && str_contains($this->priceUnit, 'osoba');
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection
    {
        return $this->reservations;
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

    public function isShowOnHomepage(): bool
    {
        return $this->showOnHomepage;
    }

    public function setShowOnHomepage(bool $showOnHomepage): static
    {
        $this->showOnHomepage = $showOnHomepage;

        return $this;
    }

    /** @return Collection<int, Image> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setRoom($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getRoom() === $this) {
                $image->setRoom(null);
            }
        }

        return $this;
    }

    public function getMainImage(): ?Image
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        return $this->images->first() ?: null;
    }

    public function getPriceLabel(): ?string
    {
        if (null === $this->price) {
            return null;
        }

        return ($this->priceFrom ? 'od ' : '').number_format($this->price, 0, ',', ' ').' Kč';
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
