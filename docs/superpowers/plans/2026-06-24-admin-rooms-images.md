# Admin Rooms Management + Image Subsystem Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add admin-managed rooms (`/admin/rooms`) backed by the database, render the public `/ubytování` page from the DB, and provide a reusable image upload + `.webp` processing subsystem.

**Architecture:** Doctrine `Room` and `Image` entities (generic `Image` with nullable `room` FK). An `ImageProcessor` service (Intervention Image v3 on GD) resizes uploads into two `.webp` variants written through an `ImageStorageInterface`. An admin shell with its own layout + Stimulus entrypoint hosts a Symfony Form + UX Dropzone CRUD. A seed command imports the 8 existing static rooms and their photos.

**Tech Stack:** Symfony 8, PHP 8.5, Doctrine ORM 3 (MySQL 8), Intervention Image v3 + GD, Symfony UX Dropzone (Stimulus), Twig, PHPUnit.

## Global Constraints

- All PHP/console/composer commands run **inside the container**: `docker compose exec -T php <cmd>` (no `php` on host). App served at `https://localhost` (self-signed → `curl -k`).
- Doctrine migrations are **always generated via `doctrine:migrations:diff`, never hand-written**.
- CSRF for admin forms uses **session (stateful) tokens** — the app does not boot Stimulus on public pages; admin gets its own entrypoint. `submit` token id stays stateless (see [config/packages/csrf.yaml](../../../config/packages/csrf.yaml)).
- Admin routes live under `^/admin` and require `ROLE_ADMIN` (already enforced in [config/packages/security.yaml](../../../config/packages/security.yaml)).
- Uploads are written to `public/uploads/` (served directly by Caddy, not asset-mapper).
- Image variants: **full** = max 1600px wide, **thumb** = max 600px wide, both `.webp` quality 82. Originals are not kept.
- Follow existing code style: `final class`, typed properties, attribute routing/mapping.

---

### Task 1: Image infrastructure — GD extension + Composer deps

**Files:**
- Modify: `Dockerfile` (base stage extension list)
- Modify: `composer.json` / `composer.lock` (via `composer require`)

**Interfaces:**
- Produces: GD with webp (`imagewebp`), `Intervention\Image\ImageManager`, `Symfony\UX\Dropzone\Form\DropzoneType` available.

- [ ] **Step 1: Add GD to the base image extension list**

In `Dockerfile`, the `frankenphp_base` stage has an `install-php-extensions` block. Add `gd`:

```dockerfile
	install-php-extensions \
		@composer \
		apcu \
		gd \
		intl \
		opcache \
		pdo_mysql \
		zip
```

- [ ] **Step 2: Rebuild the php image and verify GD+webp**

Run:
```bash
docker compose up -d --build php
docker compose exec -T php php -r 'exit(function_exists("imagewebp") ? 0 : 1);' && echo "GD+webp OK"
```
Expected: `GD+webp OK`

- [ ] **Step 3: Require Composer dependencies**

Run:
```bash
docker compose exec -T php composer require intervention/image symfony/ux-dropzone
```
Expected: both installed, `composer.lock` updated, `assets/controllers.json` gains a dropzone entry (added by the Flex recipe).

- [ ] **Step 4: Verify classes resolve**

Run:
```bash
docker compose exec -T php php -r 'require "vendor/autoload.php"; new Intervention\Image\ImageManager(new Intervention\Image\Drivers\Gd\Driver()); echo class_exists(Symfony\UX\Dropzone\Form\DropzoneType::class) ? "OK" : "MISSING";'
```
Expected: `OK`

- [ ] **Step 5: Commit**

```bash
git add Dockerfile composer.json composer.lock symfony.lock assets/controllers.json
git commit -m "build: add GD extension, intervention/image and ux-dropzone"
```

---

### Task 2: Room entity + repository

**Files:**
- Create: `src/Entity/Room.php`
- Create: `src/Repository/RoomRepository.php`
- Test: `tests/Entity/RoomTest.php`

**Interfaces:**
- Produces: `App\Entity\Room` with `getId(): ?int`, `getName(): ?string`, `setName(string): static`, `getSlug(): ?string`, `setSlug(string): static`, `getDescription(): ?string`, `setDescription(?string): static`, `getFeatures(): array`, `setFeatures(array): static`, `getPrice(): ?int`, `setPrice(?int): static`, `isPriceFrom(): bool`, `setPriceFrom(bool): static`, `getPriceUnit(): ?string`, `setPriceUnit(?string): static`, `getPosition(): int`, `setPosition(int): static`, `getImages(): Collection`, `addImage(Image): static`, `removeImage(Image): static`, `getCreatedAt(): ?\DateTimeImmutable`, `getUpdatedAt(): ?\DateTimeImmutable`, `getPriceLabel(): ?string`, `getMainImage(): ?Image`. `RoomRepository::findAllOrdered(): Room[]`.

- [ ] **Step 1: Write the failing test**

Create `tests/Entity/RoomTest.php`:

```php
<?php

namespace App\Tests\Entity;

use App\Entity\Room;
use PHPUnit\Framework\TestCase;

final class RoomTest extends TestCase
{
    public function testPriceLabelWithFromPrefix(): void
    {
        $room = (new Room())->setPrice(590)->setPriceFrom(true)->setPriceUnit('/ osoba / noc');
        self::assertSame('od 590 Kč', $room->getPriceLabel());
    }

    public function testPriceLabelWithoutPrefix(): void
    {
        $room = (new Room())->setPrice(800)->setPriceFrom(false)->setPriceUnit('/ noc (2+ noci)');
        self::assertSame('800 Kč', $room->getPriceLabel());
    }

    public function testPriceLabelNullWhenNoPrice(): void
    {
        self::assertNull((new Room())->getPriceLabel());
    }

    public function testFeaturesDefaultEmptyArray(): void
    {
        self::assertSame([], (new Room())->getFeatures());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Entity/RoomTest.php`
Expected: FAIL — `Class "App\Entity\Room" not found`.

- [ ] **Step 3: Write the Room entity**

Create `src/Entity/Room.php`:

```php
<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $features = [];

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\Column]
    private bool $priceFrom = false;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $priceUnit = null;

    #[ORM\Column]
    private int $position = 0;

    /** @var Collection<int, Image> */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'room', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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
```

- [ ] **Step 4: Write the repository**

Create `src/Repository/RoomRepository.php`:

```php
<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /** @return Room[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.position', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

> Note: `Room` references `Image` (Task 3). The entity will not be schema-valid until Task 3 exists, but this unit test only constructs `Room` in memory and passes.

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -T php php bin/phpunit tests/Entity/RoomTest.php`
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Entity/Room.php src/Repository/RoomRepository.php tests/Entity/RoomTest.php
git commit -m "feat: add Room entity and repository"
```

---

### Task 3: Image entity + repository + migration

**Files:**
- Create: `src/Entity/Image.php`
- Create: `src/Repository/ImageRepository.php`
- Create: `migrations/VersionXXXXXXXXXXXXXX.php` (generated)
- Test: `tests/Entity/ImageTest.php`

**Interfaces:**
- Consumes: `App\Entity\Room`.
- Produces: `App\Entity\Image` with `getId(): ?int`, `getFilename()/setFilename(string)`, `getThumbnail()/setThumbnail(string)`, `getOriginalName()/setOriginalName(string)`, `getAlt()/setAlt(?string)`, `getWidth()/setWidth(int)`, `getHeight()/setHeight(int)`, `getSize()/setSize(int)`, `getPosition()/setPosition(int)`, `isMain()/setIsMain(bool)`, `getRoom()/setRoom(?Room)`, `getCreatedAt()`. `ImageRepository` (default).

- [ ] **Step 1: Write the failing test**

Create `tests/Entity/ImageTest.php`:

```php
<?php

namespace App\Tests\Entity;

use App\Entity\Image;
use App\Entity\Room;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    public function testRoomRelationStaysConsistent(): void
    {
        $room = new Room();
        $image = (new Image())->setFilename('a.webp')->setThumbnail('a-thumb.webp');
        $room->addImage($image);

        self::assertSame($room, $image->getRoom());
        self::assertTrue($room->getImages()->contains($image));
    }

    public function testDefaults(): void
    {
        $image = new Image();
        self::assertFalse($image->isMain());
        self::assertSame(0, $image->getPosition());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Entity/ImageTest.php`
Expected: FAIL — `Class "App\Entity\Image" not found`.

- [ ] **Step 3: Write the Image entity**

Create `src/Entity/Image.php`:

```php
<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column]
    private int $width = 0;

    #[ORM\Column]
    private int $height = 0;

    #[ORM\Column]
    private int $size = 0;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isMain = false;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Room $room = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

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

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): static
    {
        $this->isMain = $isMain;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 4: Write the repository**

Create `src/Repository/ImageRepository.php`:

```php
<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }
}
```

- [ ] **Step 5: Run unit test + validate mapping**

Run:
```bash
docker compose exec -T php php bin/phpunit tests/Entity/ImageTest.php
docker compose exec -T php php bin/console doctrine:schema:validate --skip-sync
```
Expected: tests PASS; mapping reports `[OK] The mapping files are correct.`

- [ ] **Step 6: Generate the migration via diff**

Run:
```bash
docker compose exec -T php php bin/console doctrine:migrations:diff --no-interaction
```
Expected: a new `migrations/VersionXXXXXXXXXXXXXX.php` creating `room` and `image` tables with the `image.room_id` FK. Open it and confirm it contains `CREATE TABLE room` and `CREATE TABLE image` and a foreign key on `room_id` — do NOT edit it by hand.

- [ ] **Step 7: Run the migration**

Run:
```bash
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
```
Expected: `[OK] Successfully migrated`.

- [ ] **Step 8: Commit**

```bash
git add src/Entity/Image.php src/Repository/ImageRepository.php tests/Entity/ImageTest.php migrations/
git commit -m "feat: add Image entity, repository and rooms/images migration"
```

---

### Task 4: Image storage abstraction

**Files:**
- Create: `src/Service/Image/ImageStorageInterface.php`
- Create: `src/Service/Image/LocalImageStorage.php`
- Test: `tests/Service/Image/LocalImageStorageTest.php`

**Interfaces:**
- Produces:
  - `ImageStorageInterface::save(string $binaryContents, string $relativePath): string` — writes the file, returns the public web path (e.g. `/uploads/rooms/x/y.webp`).
  - `ImageStorageInterface::delete(string $webPath): void` — removes a previously stored file (no-op if missing).
  - `LocalImageStorage` constructed with `string $uploadsDir` (filesystem) + `string $publicPrefix` (`/uploads`).

- [ ] **Step 1: Write the failing test**

Create `tests/Service/Image/LocalImageStorageTest.php`:

```php
<?php

namespace App\Tests\Service\Image;

use App\Service\Image\LocalImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class LocalImageStorageTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/img-store-test-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testSaveWritesFileAndReturnsWebPath(): void
    {
        $storage = new LocalImageStorage($this->dir, '/uploads');
        $webPath = $storage->save('binary-bytes', 'rooms/demo/pic.webp');

        self::assertSame('/uploads/rooms/demo/pic.webp', $webPath);
        self::assertFileExists($this->dir.'/rooms/demo/pic.webp');
        self::assertSame('binary-bytes', file_get_contents($this->dir.'/rooms/demo/pic.webp'));
    }

    public function testDeleteRemovesFile(): void
    {
        $storage = new LocalImageStorage($this->dir, '/uploads');
        $storage->save('x', 'rooms/demo/pic.webp');
        $storage->delete('/uploads/rooms/demo/pic.webp');

        self::assertFileDoesNotExist($this->dir.'/rooms/demo/pic.webp');
    }

    public function testDeleteMissingFileIsNoop(): void
    {
        $storage = new LocalImageStorage($this->dir, '/uploads');
        $storage->delete('/uploads/does/not/exist.webp');
        $this->addToAssertionCount(1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Service/Image/LocalImageStorageTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the interface**

Create `src/Service/Image/ImageStorageInterface.php`:

```php
<?php

namespace App\Service\Image;

interface ImageStorageInterface
{
    /**
     * Persist binary contents at the given relative path, returns the public web path.
     */
    public function save(string $binaryContents, string $relativePath): string;

    /**
     * Remove a file previously returned by save(). No-op if it does not exist.
     */
    public function delete(string $webPath): void;
}
```

- [ ] **Step 4: Write the local implementation**

Create `src/Service/Image/LocalImageStorage.php`:

```php
<?php

namespace App\Service\Image;

use Symfony\Component\Filesystem\Filesystem;

final class LocalImageStorage implements ImageStorageInterface
{
    private Filesystem $fs;

    public function __construct(
        private readonly string $uploadsDir,
        private readonly string $publicPrefix = '/uploads',
    ) {
        $this->fs = new Filesystem();
    }

    public function save(string $binaryContents, string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $absolute = $this->uploadsDir.'/'.$relativePath;
        $this->fs->mkdir(\dirname($absolute));
        $this->fs->dumpFile($absolute, $binaryContents);

        return rtrim($this->publicPrefix, '/').'/'.$relativePath;
    }

    public function delete(string $webPath): void
    {
        $relative = ltrim(str_replace($this->publicPrefix, '', $webPath), '/');
        $absolute = $this->uploadsDir.'/'.$relative;
        if ($this->fs->exists($absolute)) {
            $this->fs->remove($absolute);
        }
    }
}
```

- [ ] **Step 5: Wire the service arguments**

In `config/services.yaml`, under `services:` (after the `App\:` resource block), add:

```yaml
    App\Service\Image\LocalImageStorage:
        arguments:
            $uploadsDir: '%kernel.project_dir%/public/uploads'
            $publicPrefix: '/uploads'

    App\Service\Image\ImageStorageInterface: '@App\Service\Image\LocalImageStorage'
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker compose exec -T php php bin/phpunit tests/Service/Image/LocalImageStorageTest.php`
Expected: PASS (3 tests).

- [ ] **Step 7: Commit**

```bash
git add src/Service/Image/ config/services.yaml tests/Service/Image/LocalImageStorageTest.php
git commit -m "feat: add image storage abstraction with local filesystem driver"
```

---

### Task 5: ImageProcessor service

**Files:**
- Create: `src/Service/Image/ImageProcessor.php`
- Test: `tests/Service/Image/ImageProcessorTest.php`

**Interfaces:**
- Consumes: `ImageStorageInterface`, `App\Entity\Image`.
- Produces: `ImageProcessor::process(\Symfony\Component\HttpFoundation\File\UploadedFile $file, string $relativeDir): Image` — generates full (≤1600w) + thumb (≤600w) webp, saves both via storage, returns a populated `Image` (filename, thumbnail, originalName, width, height, size). Caller sets `room`, `position`, `alt`, `isMain`.

- [ ] **Step 1: Write the failing test**

Create `tests/Service/Image/ImageProcessorTest.php`:

```php
<?php

namespace App\Tests\Service\Image;

use App\Service\Image\ImageProcessor;
use App\Service\Image\ImageStorageInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageProcessorTest extends TestCase
{
    public function testProcessGeneratesTwoWebpVariants(): void
    {
        // Build a 2000x1000 source PNG in a temp file.
        $manager = new ImageManager(new Driver());
        $source = $manager->create(2000, 1000)->fill('cccccc');
        $srcPath = sys_get_temp_dir().'/proc-src-'.bin2hex(random_bytes(4)).'.png';
        $source->save($srcPath);

        $saved = [];
        $storage = new class($saved) implements ImageStorageInterface {
            /** @param array<string,string> $saved */
            public function __construct(private array &$saved) {}
            public function save(string $binaryContents, string $relativePath): string
            {
                $this->saved[$relativePath] = $binaryContents;
                return '/uploads/'.$relativePath;
            }
            public function delete(string $webPath): void {}
        };

        $processor = new ImageProcessor($storage);
        $upload = new UploadedFile($srcPath, 'PHOTO.PNG', 'image/png', null, true);
        $image = $processor->process($upload, 'rooms/demo');

        self::assertCount(2, $saved);
        self::assertStringStartsWith('/uploads/rooms/demo/', $image->getFilename());
        self::assertStringEndsWith('.webp', $image->getFilename());
        self::assertStringEndsWith('.webp', $image->getThumbnail());
        self::assertSame('PHOTO.PNG', $image->getOriginalName());
        self::assertSame(1600, $image->getWidth(), 'full variant scaled to 1600 wide');
        self::assertSame(800, $image->getHeight());
        self::assertGreaterThan(0, $image->getSize());

        // Stored bytes must be valid webp.
        $full = $saved[ltrim($image->getFilename(), '/uploads/') === false ? '' : substr($image->getFilename(), strlen('/uploads/'))];
        self::assertSame('WEBP', substr($full, 8, 4));

        @unlink($srcPath);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Service/Image/ImageProcessorTest.php`
Expected: FAIL — `Class "App\Service\Image\ImageProcessor" not found`.

- [ ] **Step 3: Write the ImageProcessor**

Create `src/Service/Image/ImageProcessor.php`:

```php
<?php

namespace App\Service\Image;

use App\Entity\Image;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageProcessor
{
    private const FULL_WIDTH = 1600;
    private const THUMB_WIDTH = 600;
    private const QUALITY = 82;

    private ImageManager $manager;

    public function __construct(
        private readonly ImageStorageInterface $storage,
    ) {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * @param string $relativeDir e.g. "rooms/double-shared"
     */
    public function process(UploadedFile $file, string $relativeDir): Image
    {
        $relativeDir = trim($relativeDir, '/');
        $basename = bin2hex(random_bytes(8));

        // Intervention v3 honours EXIF orientation on read.
        $full = $this->manager->read($file->getPathname());
        $full->scaleDown(width: self::FULL_WIDTH);
        $fullBytes = (string) $full->toWebp(self::QUALITY);
        $fullPath = $this->storage->save($fullBytes, "$relativeDir/$basename.webp");

        $thumb = $this->manager->read($file->getPathname());
        $thumb->scaleDown(width: self::THUMB_WIDTH);
        $thumbBytes = (string) $thumb->toWebp(self::QUALITY);
        $thumbPath = $this->storage->save($thumbBytes, "$relativeDir/$basename-thumb.webp");

        return (new Image())
            ->setFilename($fullPath)
            ->setThumbnail($thumbPath)
            ->setOriginalName($file->getClientOriginalName())
            ->setWidth($full->width())
            ->setHeight($full->height())
            ->setSize(\strlen($fullBytes));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec -T php php bin/phpunit tests/Service/Image/ImageProcessorTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Service/Image/ImageProcessor.php tests/Service/Image/ImageProcessorTest.php
git commit -m "feat: add ImageProcessor generating webp full+thumb variants"
```

---

### Task 6: Admin shell layout + menu, fix dashboard

**Files:**
- Create: `templates/admin/base.html.twig`
- Modify: `src/Controller/DashboardController.php`
- Modify: `templates/dashboard/index.html.twig`
- Modify: `tests/Controller/DashboardControllerTest.php`

**Interfaces:**
- Produces: `templates/admin/base.html.twig` with blocks `title`, `page_title`, `body`, `admin_javascripts`; a sidebar listing menu items (active state by `app.request.attributes.get('_route')`); the first item links to `app_admin_rooms_index` (Task 8). Until Task 8 exists, link to `app_dashboard` to keep the template renderable, then switch in Task 8.

- [ ] **Step 1: Fix the failing dashboard test**

Replace `tests/Controller/DashboardControllerTest.php` with an auth-aware test:

```php
<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardControllerTest extends WebTestCase
{
    public function testAnonymousRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/admin/login');
    }

    public function testAdminCanSeeDashboard(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())->setEmail('test-admin@example.com')->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'pw'));
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();

        $em->remove($user);
        $em->flush();
    }
}
```

- [ ] **Step 2: Prepare the test database and run the test (expect dashboard still uses old template → may still pass redirect, fail success)**

Run (one-time test DB setup, safe to re-run):
```bash
docker compose exec -T php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec -T php php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec -T php php bin/phpunit tests/Controller/DashboardControllerTest.php
```
Expected: `testAnonymousRedirectedToLogin` PASS; `testAdminCanSeeDashboard` PASS (the existing template renders). If both pass, proceed; the next steps replace the template with the admin shell.

- [ ] **Step 3: Write the admin base layout**

Create `templates/admin/base.html.twig`:

```twig
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>{% block title %}Administrace{% endblock %} – Tesák-Čerňava</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', system-ui, sans-serif; color: #1f2d27; background: #f4f6f5; display: flex; min-height: 100vh; }
    .admin-sidebar { width: 230px; background: #1a2b22; color: #cdd8d1; display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
    .admin-sidebar__brand { font-weight: 700; font-size: 1.1rem; color: #fff; padding: 0 1.5rem 1.5rem; }
    .admin-sidebar__link { display: block; padding: .65rem 1.5rem; color: #cdd8d1; text-decoration: none; font-size: .9rem; }
    .admin-sidebar__link:hover, .admin-sidebar__link.active { background: #2f5d45; color: #fff; }
    .admin-sidebar__logout { margin-top: auto; padding: 0 1.5rem; }
    .admin-sidebar__logout a { color: #9fb3a8; font-size: .8rem; text-decoration: none; }
    .admin-main { flex: 1; padding: 2rem 2.5rem; max-width: 1100px; }
    .admin-main__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
    .admin-main__header h1 { font-size: 1.5rem; }
    .btn { display: inline-block; padding: .55rem .9rem; border-radius: 8px; font-size: .9rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
    .btn--primary { background: #2f5d45; color: #fff; }
    .btn--danger { background: #b02a25; color: #fff; }
    .btn--ghost { background: #e6ebe8; color: #1f2d27; }
    .flash { padding: .7rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
    .flash--success { background: #e7f4ec; color: #1f6b3f; }
    .flash--error { background: #fdecea; color: #b02a25; }
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; }
    th, td { text-align: left; padding: .75rem 1rem; border-bottom: 1px solid #eef1ef; font-size: .9rem; }
    th { background: #f0f3f1; font-weight: 600; }
    label { display: block; font-size: .8rem; font-weight: 600; margin: 1rem 0 .35rem; }
    input[type=text], input[type=number], textarea, select { width: 100%; padding: .6rem .75rem; border: 1px solid #d4dbd6; border-radius: 8px; font-family: inherit; font-size: .9rem; }
    .form-card { background: #fff; border-radius: 10px; padding: 1.5rem; }
  </style>
  {% block stylesheets %}{% endblock %}
</head>
<body>
  <aside class="admin-sidebar">
    {% set route = app.request.attributes.get('_route') %}
    <div class="admin-sidebar__brand">Tesák-Čerňava</div>
    <nav>
      <a class="admin-sidebar__link{{ route starts with 'app_admin_rooms' ? ' active' : '' }}" href="{{ path('app_dashboard') }}">Pokoje</a>
    </nav>
    <div class="admin-sidebar__logout">
      <a href="{{ path('app_logout') }}">Odhlásit se</a>
    </div>
  </aside>
  <main class="admin-main">
    <div class="admin-main__header">
      <h1>{% block page_title %}{% endblock %}</h1>
      {% block page_actions %}{% endblock %}
    </div>
    {% for label, messages in app.flashes %}
      {% for message in messages %}
        <div class="flash flash--{{ label }}">{{ message }}</div>
      {% endfor %}
    {% endfor %}
    {% block body %}{% endblock %}
  </main>
  {% block admin_javascripts %}{% endblock %}
</body>
</html>
```

- [ ] **Step 4: Point the dashboard at the admin shell**

Replace `templates/dashboard/index.html.twig`:

```twig
{% extends 'admin/base.html.twig' %}

{% block title %}Administrace{% endblock %}
{% block page_title %}Administrace{% endblock %}

{% block body %}
  <div class="form-card">
    <p>Vítejte v administraci. Vlevo v menu vyberte sekci.</p>
  </div>
{% endblock %}
```

Leave `DashboardController` as-is (route `/admin`, `ROLE_ADMIN`).

- [ ] **Step 5: Run the dashboard test**

Run: `docker compose exec -T php php bin/phpunit tests/Controller/DashboardControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add templates/admin/base.html.twig templates/dashboard/index.html.twig tests/Controller/DashboardControllerTest.php
git commit -m "feat: add admin shell layout and menu, fix dashboard test"
```

---

### Task 7: Admin Stimulus entrypoint (boots UX Dropzone)

**Files:**
- Create: `assets/admin.js`
- Create: `assets/bootstrap.js` (populate — currently empty)
- Modify: `importmap.php`
- Modify: `templates/admin/base.html.twig` (load the entrypoint)

**Interfaces:**
- Produces: an `admin` importmap entrypoint that boots Stimulus via `bootstrap.js` (which calls `startStimulusApp()`), making the `dropzone` UX controller available on admin pages.

- [ ] **Step 1: Populate the Stimulus bootstrap**

Create/replace `assets/bootstrap.js`:

```js
import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
export { app };
```

- [ ] **Step 2: Create the admin entrypoint**

Create `assets/admin.js`:

```js
import './bootstrap.js';
```

- [ ] **Step 3: Register the entrypoint in importmap**

In `importmap.php`, add an `admin` entry alongside the existing `app` entry:

```php
    'admin' => [
        'path' => './assets/admin.js',
        'entrypoint' => true,
    ],
```

- [ ] **Step 4: Load the entrypoint in the admin layout**

In `templates/admin/base.html.twig`, replace the `admin_javascripts` block:

```twig
  {% block admin_javascripts %}{{ importmap('admin') }}{% endblock %}
```

- [ ] **Step 5: Verify the entrypoint compiles**

Run:
```bash
docker compose exec -T php php bin/console asset-map:compile --env=dev 2>&1 | tail -3
docker compose exec -T php php bin/console debug:asset-map 2>&1 | grep -E 'admin\.js|bootstrap\.js'
```
Expected: no errors; `admin.js` and `bootstrap.js` listed as mapped assets.

- [ ] **Step 6: Commit**

```bash
git add assets/admin.js assets/bootstrap.js importmap.php templates/admin/base.html.twig
git commit -m "feat: add admin Stimulus entrypoint for UX Dropzone"
```

---

### Task 8: Room CRUD (list / new / edit fields / delete)

**Files:**
- Create: `src/Controller/Admin/RoomController.php`
- Create: `src/Form/RoomType.php`
- Create: `templates/admin/room/index.html.twig`
- Create: `templates/admin/room/new.html.twig`
- Create: `templates/admin/room/edit.html.twig`
- Create: `templates/admin/room/_form.html.twig`
- Modify: `templates/admin/base.html.twig` (menu link → real route)
- Test: `tests/Controller/Admin/RoomControllerTest.php`

**Interfaces:**
- Consumes: `Room`, `RoomRepository`, `EntityManagerInterface`.
- Produces routes: `app_admin_rooms_index` (`GET /admin/rooms`), `app_admin_rooms_new` (`GET|POST /admin/rooms/new`), `app_admin_rooms_edit` (`GET|POST /admin/rooms/{id}/edit`), `app_admin_rooms_delete` (`POST /admin/rooms/{id}/delete`).
- `RoomType` maps `name`, `slug`, `description`, `features` (CollectionType of text, allow add/delete), `price`, `priceFrom`, `priceUnit`, `position`.

- [ ] **Step 1: Write the failing functional test**

Create `tests/Controller/Admin/RoomControllerTest.php`:

```php
<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RoomControllerTest extends WebTestCase
{
    private function loginAdmin($client): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = (new User())->setEmail('room-admin@example.com')->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'pw'));
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);
    }

    public function testAnonymousBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/rooms');
        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateRoom(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/admin/rooms/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Uložit')->form([
            'room[name]' => 'Test Pokoj',
            'room[slug]' => 'test-pokoj',
            'room[priceUnit]' => '/ noc',
            'room[price]' => '1234',
            'room[position]' => '5',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/admin/rooms');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $room = $em->getRepository(Room::class)->findOneBy(['slug' => 'test-pokoj']);
        self::assertNotNull($room);
        self::assertSame('Test Pokoj', $room->getName());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Controller/Admin/RoomControllerTest.php`
Expected: FAIL — `/admin/rooms` 404 (route missing).

- [ ] **Step 3: Write the form type**

Create `src/Form/RoomType.php`:

```php
<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název'])
            ->add('slug', TextType::class, ['label' => 'Slug (klíč galerie)'])
            ->add('description', TextareaType::class, ['label' => 'Popis', 'required' => false])
            ->add('features', CollectionType::class, [
                'label' => 'Vlastnosti',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
            ->add('price', IntegerType::class, ['label' => 'Cena (Kč)', 'required' => false])
            ->add('priceFrom', CheckboxType::class, ['label' => 'Cena "od"', 'required' => false])
            ->add('priceUnit', TextType::class, ['label' => 'Jednotka ceny', 'required' => false])
            ->add('position', IntegerType::class, ['label' => 'Pořadí']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Room::class]);
    }
}
```

- [ ] **Step 4: Write the controller**

Create `src/Controller/Admin/RoomController.php`:

```php
<?php

namespace App\Controller\Admin;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rooms')]
#[IsGranted('ROLE_ADMIN')]
final class RoomController extends AbstractController
{
    #[Route('', name: 'app_admin_rooms_index', methods: ['GET'])]
    public function index(RoomRepository $rooms): Response
    {
        return $this->render('admin/room/index.html.twig', [
            'rooms' => $rooms->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_admin_rooms_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($room);
            $em->flush();
            $this->addFlash('success', 'Pokoj vytvořen.');

            return $this->redirectToRoute('app_admin_rooms_index');
        }

        return $this->render('admin/room/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_admin_rooms_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Room $room, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Pokoj uložen.');

            return $this->redirectToRoute('app_admin_rooms_index');
        }

        return $this->render('admin/room/edit.html.twig', ['form' => $form, 'room' => $room]);
    }

    #[Route('/{id}/delete', name: 'app_admin_rooms_delete', methods: ['POST'])]
    public function delete(Request $request, Room $room, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), (string) $request->request->get('_token'))) {
            $em->remove($room);
            $em->flush();
            $this->addFlash('success', 'Pokoj smazán.');
        }

        return $this->redirectToRoute('app_admin_rooms_index');
    }
}
```

- [ ] **Step 5: Write the templates**

Create `templates/admin/room/index.html.twig`:

```twig
{% extends 'admin/base.html.twig' %}
{% block title %}Pokoje{% endblock %}
{% block page_title %}Pokoje{% endblock %}
{% block page_actions %}<a class="btn btn--primary" href="{{ path('app_admin_rooms_new') }}">Nový pokoj</a>{% endblock %}

{% block body %}
  <table>
    <thead><tr><th>Pořadí</th><th>Název</th><th>Cena</th><th>Fotek</th><th></th></tr></thead>
    <tbody>
    {% for room in rooms %}
      <tr>
        <td>{{ room.position }}</td>
        <td>{{ room.name }}</td>
        <td>{{ room.priceLabel ?? '—' }} {{ room.priceUnit }}</td>
        <td>{{ room.images|length }}</td>
        <td>
          <a class="btn btn--ghost" href="{{ path('app_admin_rooms_edit', {id: room.id}) }}">Upravit</a>
          <form method="post" action="{{ path('app_admin_rooms_delete', {id: room.id}) }}" style="display:inline" onsubmit="return confirm('Smazat pokoj?')">
            <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ room.id) }}">
            <button class="btn btn--danger">Smazat</button>
          </form>
        </td>
      </tr>
    {% else %}
      <tr><td colspan="5">Žádné pokoje.</td></tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```

Create `templates/admin/room/_form.html.twig`:

```twig
{{ form_start(form) }}
  <div class="form-card">
    {{ form_row(form.name) }}
    {{ form_row(form.slug) }}
    {{ form_row(form.description) }}
    {{ form_row(form.features) }}
    {{ form_row(form.price) }}
    {{ form_row(form.priceFrom) }}
    {{ form_row(form.priceUnit) }}
    {{ form_row(form.position) }}
    <div style="margin-top:1rem">
      <button class="btn btn--primary">Uložit</button>
      <a class="btn btn--ghost" href="{{ path('app_admin_rooms_index') }}">Zpět</a>
    </div>
  </div>
{{ form_end(form) }}
```

Create `templates/admin/room/new.html.twig`:

```twig
{% extends 'admin/base.html.twig' %}
{% block title %}Nový pokoj{% endblock %}
{% block page_title %}Nový pokoj{% endblock %}
{% block body %}{{ include('admin/room/_form.html.twig') }}{% endblock %}
```

Create `templates/admin/room/edit.html.twig`:

```twig
{% extends 'admin/base.html.twig' %}
{% block title %}Upravit pokoj{% endblock %}
{% block page_title %}Upravit: {{ room.name }}{% endblock %}
{% block body %}
  {{ include('admin/room/_form.html.twig') }}
  {# Gallery management injected in Task 9 #}
  {% block gallery %}{% endblock %}
{% endblock %}
```

- [ ] **Step 6: Point the menu at the real route**

In `templates/admin/base.html.twig`, change the Pokoje link `href` from `path('app_dashboard')` to `path('app_admin_rooms_index')`.

- [ ] **Step 7: Run the test**

Run: `docker compose exec -T php php bin/phpunit tests/Controller/Admin/RoomControllerTest.php`
Expected: PASS (3 tests).

- [ ] **Step 8: Commit**

```bash
git add src/Controller/Admin/RoomController.php src/Form/RoomType.php templates/admin/room/ templates/admin/base.html.twig tests/Controller/Admin/RoomControllerTest.php
git commit -m "feat: add room CRUD in admin"
```

---

### Task 9: Room gallery management (upload, delete, reorder, set main)

**Files:**
- Modify: `src/Controller/Admin/RoomController.php` (add `uploadImages`, `deleteImage`, `reorderImages`, `setMainImage` actions)
- Modify: `templates/admin/room/edit.html.twig` (override `gallery` block)
- Test: `tests/Controller/Admin/RoomImageTest.php`

**Interfaces:**
- Consumes: `ImageProcessor::process()`, `Room`, `Image`, `ImageStorageInterface`, `EntityManagerInterface`.
- Produces routes: `app_admin_rooms_images_upload` (`POST /admin/rooms/{id}/images`), `app_admin_rooms_images_delete` (`POST /admin/rooms/{id}/images/{imageId}/delete`), `app_admin_rooms_images_main` (`POST /admin/rooms/{id}/images/{imageId}/main`).

- [ ] **Step 1: Write the failing functional test**

Create `tests/Controller/Admin/RoomImageTest.php`:

```php
<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RoomImageTest extends WebTestCase
{
    public function testUploadImageToRoom(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())->setEmail('img-admin@example.com')->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'pw'));
        $room = (new Room())->setName('Galerie')->setSlug('galerie-test')->setPriceUnit('/ noc');
        $em->persist($user);
        $em->persist($room);
        $em->flush();
        $client->loginUser($user);

        // build a temp png upload
        $manager = new ImageManager(new Driver());
        $path = sys_get_temp_dir().'/up-'.bin2hex(random_bytes(4)).'.png';
        $manager->create(800, 600)->fill('aabbcc')->save($path);
        $upload = new UploadedFile($path, 'test.png', 'image/png', null, true);

        $client->request(
            'POST',
            '/admin/rooms/'.$room->getId().'/images',
            ['_token' => static::getContainer()->get('security.csrf.token_manager')->getToken('upload'.$room->getId())->getValue()],
            ['images' => [$upload]],
        );
        self::assertResponseRedirects('/admin/rooms/'.$room->getId().'/edit');

        $em->clear();
        $reloaded = $em->getRepository(Room::class)->find($room->getId());
        self::assertCount(1, $reloaded->getImages());
        @unlink($path);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Controller/Admin/RoomImageTest.php`
Expected: FAIL — route `/admin/rooms/{id}/images` 404.

- [ ] **Step 3: Add image actions to the controller**

Add these `use` statements and methods to `src/Controller/Admin/RoomController.php`:

```php
use App\Entity\Image;
use App\Service\Image\ImageProcessor;
use App\Service\Image\ImageStorageInterface;
```

```php
    #[Route('/{id}/images', name: 'app_admin_rooms_images_upload', methods: ['POST'])]
    public function uploadImages(Request $request, Room $room, ImageProcessor $processor, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('upload'.$room->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $position = $room->getImages()->count();
        foreach ($request->files->all()['images'] ?? [] as $file) {
            if (null === $file) {
                continue;
            }
            $image = $processor->process($file, 'rooms/'.$room->getSlug());
            $image->setPosition($position++);
            if ($room->getImages()->isEmpty() && 0 === $room->getImages()->count()) {
                $image->setIsMain(true);
            }
            $room->addImage($image);
            $em->persist($image);
        }
        $em->flush();
        $this->addFlash('success', 'Fotky nahrány.');

        return $this->redirectToRoute('app_admin_rooms_edit', ['id' => $room->getId()]);
    }

    #[Route('/{id}/images/{imageId}/delete', name: 'app_admin_rooms_images_delete', methods: ['POST'])]
    public function deleteImage(Request $request, Room $room, int $imageId, ImageStorageInterface $storage, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('img-delete'.$imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($room->getImages() as $image) {
            if ($image->getId() === $imageId) {
                $storage->delete($image->getFilename());
                $storage->delete($image->getThumbnail());
                $room->removeImage($image);
                $em->remove($image);
                break;
            }
        }
        $em->flush();
        $this->addFlash('success', 'Fotka smazána.');

        return $this->redirectToRoute('app_admin_rooms_edit', ['id' => $room->getId()]);
    }

    #[Route('/{id}/images/{imageId}/main', name: 'app_admin_rooms_images_main', methods: ['POST'])]
    public function setMainImage(Request $request, Room $room, int $imageId, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('img-main'.$imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($room->getImages() as $image) {
            $image->setIsMain($image->getId() === $imageId);
        }
        $em->flush();
        $this->addFlash('success', 'Hlavní fotka nastavena.');

        return $this->redirectToRoute('app_admin_rooms_edit', ['id' => $room->getId()]);
    }
```

> Note: the `uploadImages` "first image becomes main" guard is computed before `addImage`; the simpler correct form is `$makeMain = $room->getImages()->isEmpty();` captured once before the loop. Use:
> ```php
> $makeMain = $room->getImages()->isEmpty();
> // inside loop, after process:
> if ($makeMain) { $image->setIsMain(true); $makeMain = false; }
> ```

- [ ] **Step 4: Render the gallery in the edit template**

In `templates/admin/room/edit.html.twig`, replace the empty `gallery` block:

```twig
{% block gallery %}
  <div class="form-card" style="margin-top:1.5rem">
    <h2 style="font-size:1.1rem;margin-bottom:1rem">Galerie</h2>

    <form method="post" action="{{ path('app_admin_rooms_images_upload', {id: room.id}) }}" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="{{ csrf_token('upload' ~ room.id) }}">
      <div {{ stimulus_controller('symfony--ux-dropzone--dropzone') }}>
        <input type="file" name="images[]" multiple accept="image/*" data-symfony--ux-dropzone--dropzone-target="input">
      </div>
      <button class="btn btn--primary" style="margin-top:.75rem">Nahrát fotky</button>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-top:1.25rem">
      {% for image in room.images %}
        <div style="border:1px solid #eef1ef;border-radius:8px;padding:.5rem;background:#fff">
          <img src="{{ image.thumbnail }}" alt="{{ image.alt }}" style="width:100%;height:110px;object-fit:cover;border-radius:6px">
          <div style="font-size:.75rem;margin:.4rem 0">{{ image.isMain ? '★ hlavní' : '' }}</div>
          <div style="display:flex;gap:.3rem">
            {% if not image.isMain %}
            <form method="post" action="{{ path('app_admin_rooms_images_main', {id: room.id, imageId: image.id}) }}">
              <input type="hidden" name="_token" value="{{ csrf_token('img-main' ~ image.id) }}">
              <button class="btn btn--ghost" style="font-size:.7rem;padding:.3rem .5rem">Hlavní</button>
            </form>
            {% endif %}
            <form method="post" action="{{ path('app_admin_rooms_images_delete', {id: room.id, imageId: image.id}) }}" onsubmit="return confirm('Smazat fotku?')">
              <input type="hidden" name="_token" value="{{ csrf_token('img-delete' ~ image.id) }}">
              <button class="btn btn--danger" style="font-size:.7rem;padding:.3rem .5rem">Smazat</button>
            </form>
          </div>
        </div>
      {% endfor %}
    </div>
  </div>
{% endblock %}
```

> Reorder by drag is deferred: rooms reorder via the `position` field; image order follows upload order via `position`. (Documented limitation — not silently dropped.)

- [ ] **Step 5: Apply the "first image main" fix and run the test**

Apply the simpler `$makeMain` guard from Step 3's note in the controller, then run:
`docker compose exec -T php php bin/phpunit tests/Controller/Admin/RoomImageTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Controller/Admin/RoomController.php templates/admin/room/edit.html.twig tests/Controller/Admin/RoomImageTest.php
git commit -m "feat: add room gallery upload, delete and set-main"
```

---

### Task 10: Seed command — import existing static rooms

**Files:**
- Create: `src/Command/ImportRoomsCommand.php`
- Test: `tests/Command/ImportRoomsCommandTest.php`

**Interfaces:**
- Consumes: `RoomRepository`, `EntityManagerInterface`, `ImageStorageInterface`, `Room`, `Image`. Reads existing files from `%kernel.project_dir%/assets/images/rooms/<slug>/`.
- Produces: command `app:import-rooms` (idempotent — skips rooms whose slug already exists).

The 8 rooms (slug, name, features, price, priceFrom, priceUnit, position) — copy these verbatim from [templates/accommodation/index.html.twig](../../../templates/accommodation/index.html.twig):

| pos | slug | name | price | from | unit |
|---|---|---|---|---|---|
| 1 | double-shared | Dvoulůžkový pokoj | 800 | no | / noc (2+ noci) |
| 2 | family-large | Rodinný pokoj | 2100 | no | / noc |
| 3 | apartment-ground | Velký apartmán | 590 | yes | / osoba / noc |
| 4 | single | Jednolůžkové obsazení | 650 | no | / noc (2+ noci) |
| 5 | double-ensuite | Pokoj s manželskou postelí | 1100 | no | / noc (2+ noci) |
| 6 | bunk-4bed | Čtyřlůžkový pokoj s palandou | 1890 | no | / noc (2+ noci) |
| 7 | family-double | Rodinný dvoupokoj | 2490 | no | / noc (2+ noci) |
| 8 | apartment-2bedroom | Apartmán 2 ložnice | 3990 | no | / noc (2+ noci) |

> The existing static images are already `.webp` under `assets/images/rooms/<slug>/`. The command copies each file into `public/uploads/rooms/<slug>/` (via the storage service, raw copy — they are already webp, no reprocessing) and creates `Image` rows. `thumbnail` is set to the same file as `filename` for seeded images (no separate thumb generated for legacy assets). Features per room are copied verbatim from the template bullet lists.

- [ ] **Step 1: Write the failing test**

Create `tests/Command/ImportRoomsCommandTest.php`:

```php
<?php

namespace App\Tests\Command;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportRoomsCommandTest extends KernelTestCase
{
    public function testImportIsIdempotent(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $application = new Application(static::$kernel);
        $tester = new CommandTester($application->find('app:import-rooms'));

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        $countAfterFirst = \count($em->getRepository(Room::class)->findAll());
        self::assertSame(8, $countAfterFirst);

        $em->clear();
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        self::assertSame(8, \count($em->getRepository(Room::class)->findAll()), 'second run creates no duplicates');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Command/ImportRoomsCommandTest.php`
Expected: FAIL — command `app:import-rooms` not found.

- [ ] **Step 3: Write the command**

Create `src/Command/ImportRoomsCommand.php`. Build a `private const ROOMS` array of the 8 rooms above, each with `slug`, `name`, `features` (the verbatim bullet strings from the template), `price`, `priceFrom`, `priceUnit`, `position`. Skeleton:

```php
<?php

namespace App\Command;

use App\Entity\Image;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Service\Image\ImageStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-rooms', description: 'Imports the 8 static rooms and their existing photos into the database.')]
final class ImportRoomsCommand extends Command
{
    private const ROOMS = [
        ['slug' => 'double-shared', 'name' => 'Dvoulůžkový pokoj', 'price' => 800, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 1,
         'features' => ['2 lůžka (možnost přistýlky)', 'Sdílená koupelna, balkon', 'Výhled do přírody', 'Sdílená kuchyňka na chodbě']],
        ['slug' => 'family-large', 'name' => 'Rodinný pokoj', 'price' => 2100, 'from' => false, 'unit' => '/ noc', 'position' => 2,
         'features' => ['Vhodný pro rodiny s dětmi', 'Vlastní koupelna se sprchovým koutem', 'Postýlka do 2 let zdarma', 'Přístup na zahradu s hřištěm']],
        ['slug' => 'apartment-ground', 'name' => 'Velký apartmán', 'price' => 590, 'from' => true, 'unit' => '/ osoba / noc', 'position' => 3,
         'features' => ['Až 9 osob', 'Vlastní kuchyňka i koupelna', 'Ideální pro rodiny a malé skupiny', 'Přízemí s vlastním vstupem']],
        ['slug' => 'single', 'name' => 'Jednolůžkové obsazení', 'price' => 650, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 4,
         'features' => ['Ubytování pro 1 osobu', 'Koupelna, ručníky, WC', 'Balkon s výhledem do přírody']],
        ['slug' => 'double-ensuite', 'name' => 'Pokoj s manželskou postelí', 'price' => 1100, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 5,
         'features' => ['Manželská postel, TV', 'Vlastní koupelna, balkon', 'Společná kuchyňka']],
        ['slug' => 'bunk-4bed', 'name' => 'Čtyřlůžkový pokoj s palandou', 'price' => 1890, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 6,
         'features' => ['Palanda + 2 lůžka (až 4 osoby)', 'Vlastní koupelna, balkon', 'Společná kuchyňka']],
        ['slug' => 'family-double', 'name' => 'Rodinný dvoupokoj', 'price' => 2490, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 7,
         'features' => ['Manž. postel + palanda + 2 lůžka, TV', 'Vlastní koupelna, balkon', 'Dva propojené pokoje']],
        ['slug' => 'apartment-2bedroom', 'name' => 'Apartmán 2 ložnice', 'price' => 3990, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 8,
         'features' => ['Až 11 osob, 2× manž. postel, palanda', 'TV, kuchyňský kout, koupelna, balkon', '2× rozkládací gauč']],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RoomRepository $rooms,
        private readonly ImageStorageInterface $storage,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach (self::ROOMS as $data) {
            if (null !== $this->rooms->findOneBy(['slug' => $data['slug']])) {
                $io->writeln(sprintf('skip %s (exists)', $data['slug']));
                continue;
            }

            $room = (new Room())
                ->setSlug($data['slug'])
                ->setName($data['name'])
                ->setFeatures($data['features'])
                ->setPrice($data['price'])
                ->setPriceFrom($data['from'])
                ->setPriceUnit($data['unit'])
                ->setPosition($data['position']);
            $this->em->persist($room);

            $sourceDir = $this->projectDir.'/assets/images/rooms/'.$data['slug'];
            $position = 0;
            if (is_dir($sourceDir)) {
                foreach (glob($sourceDir.'/*.webp') ?: [] as $path) {
                    $webPath = $this->storage->save(file_get_contents($path), 'rooms/'.$data['slug'].'/'.basename($path));
                    $image = (new Image())
                        ->setFilename($webPath)
                        ->setThumbnail($webPath)
                        ->setOriginalName(basename($path))
                        ->setPosition($position);
                    if (0 === $position) {
                        $image->setIsMain(true);
                    }
                    $room->addImage($image);
                    $this->em->persist($image);
                    ++$position;
                }
            }
            ++$created;
        }

        $this->em->flush();
        $io->success(sprintf('Imported %d rooms.', $created));

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 4: Wire the `$projectDir` argument**

In `config/services.yaml`, add:

```yaml
    App\Command\ImportRoomsCommand:
        arguments:
            $projectDir: '%kernel.project_dir%'
```

- [ ] **Step 5: Run the test**

Run: `docker compose exec -T php php bin/phpunit tests/Command/ImportRoomsCommandTest.php`
Expected: PASS (8 rooms, idempotent).

- [ ] **Step 6: Run the import against the dev database**

Run:
```bash
docker compose exec -T php php bin/console app:import-rooms
```
Expected: `Imported 8 rooms.` and files appear under `public/uploads/rooms/`.

- [ ] **Step 7: Commit**

```bash
git add src/Command/ImportRoomsCommand.php config/services.yaml tests/Command/ImportRoomsCommandTest.php
git commit -m "feat: add app:import-rooms seed command"
```

---

### Task 11: Render public /ubytování from the database

**Files:**
- Modify: `src/Controller/AccommodationController.php`
- Modify: `templates/accommodation/index.html.twig` (rooms section only)
- Create: `public/uploads/.gitignore`
- Test: `tests/Controller/AccommodationControllerTest.php`

**Interfaces:**
- Consumes: `RoomRepository::findAllOrdered()`, `Room.images`, `Room.priceLabel`, `Room.priceUnit`.

- [ ] **Step 1: Inspect the current controller**

Run: `docker compose exec -T php php bin/console debug:router app_accommodation`
Then open `src/Controller/AccommodationController.php` to see the current action signature.

- [ ] **Step 2: Write the failing test**

Create `tests/Controller/AccommodationControllerTest.php`:

```php
<?php

namespace App\Tests\Controller;

use App\Entity\Image;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccommodationControllerTest extends WebTestCase
{
    public function testRendersRoomsFromDatabase(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $room = (new Room())->setName('Unikátní Pokoj XYZ')->setSlug('unikatni-xyz')
            ->setPrice(1234)->setPriceUnit('/ noc')->setPosition(1)
            ->setFeatures(['Feature jedna', 'Feature dvě']);
        $image = (new Image())->setFilename('/uploads/x.webp')->setThumbnail('/uploads/x-thumb.webp')
            ->setOriginalName('x.webp')->setIsMain(true);
        $room->addImage($image);
        $em->persist($room);
        $em->persist($image);
        $em->flush();

        $crawler = $client->request('GET', '/ubytovani');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Unikátní Pokoj XYZ', $client->getResponse()->getContent());
        self::assertStringContainsString('1 234 Kč', $client->getResponse()->getContent());

        $em->remove($image);
        $em->remove($room);
        $em->flush();
    }
}
```

> Confirm the public URL is `/ubytovani` from Step 1's `debug:router` output; adjust the request path if the route differs.

- [ ] **Step 3: Run test to verify it fails**

Run: `docker compose exec -T php php bin/phpunit tests/Controller/AccommodationControllerTest.php`
Expected: FAIL — page still renders the hardcoded rooms, not the DB room name `Unikátní Pokoj XYZ`.

- [ ] **Step 4: Update the controller to pass rooms**

In `src/Controller/AccommodationController.php`, inject `RoomRepository` and pass rooms to the template:

```php
use App\Repository\RoomRepository;
// ...
public function index(RoomRepository $rooms): Response
{
    return $this->render('accommodation/index.html.twig', [
        'rooms' => $rooms->findAllOrdered(),
    ]);
}
```

- [ ] **Step 5: Replace the rooms grid with a DB-driven loop**

In `templates/accommodation/index.html.twig`, replace the contents of the `<div class="grid-3">` (the 8 hardcoded `<article class="room-card">` blocks, lines ~85–552 between `<div class="grid-3">` and its closing `</div>`) with:

```twig
        <div class="grid-3">
          {% for room in rooms %}
          <article class="room-card">
            <div class="room-card__gallery">
              <button class="room-card__main" data-gallery="{{ room.slug }}" type="button" aria-label="{{ room.name }} – zobrazit fotogalerii">
                {% set main = room.mainImage %}
                <img src="{{ main ? main.thumbnail : asset('images/rooms/' ~ room.slug ~ '/placeholder.webp') }}" alt="{{ main and main.alt ? main.alt : room.name }}" loading="lazy">
                <span class="room-card__photo-badge">{{ room.images|length }} fotek</span>
              </button>
              <div class="room-card__thumbs">
                {% for image in room.images|slice(1, 3) %}
                  <button class="room-card__thumb" data-gallery="{{ room.slug }}" type="button" aria-label="{{ room.name }} – fotka {{ loop.index + 1 }}">
                    <img src="{{ image.thumbnail }}" alt="{{ image.alt ?? room.name }}" loading="lazy">
                    {% if loop.last and room.images|length > 4 %}<span class="room-card__thumb-more" aria-hidden="true">+{{ room.images|length - 4 }}</span>{% endif %}
                  </button>
                {% endfor %}
                {% for image in room.images|slice(4) %}
                  <button class="room-card__thumb room-card__thumb--extra" data-gallery="{{ room.slug }}" type="button" tabindex="-1" aria-hidden="true">
                    <img src="{{ image.thumbnail }}" alt="{{ image.alt ?? room.name }}" loading="lazy">
                  </button>
                {% endfor %}
              </div>
            </div>
            <div class="room-card__body">
              <h3 class="room-card__name">{{ room.name }}</h3>
              <ul class="room-card__features">
                {% for feature in room.features %}
                <li>
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                  {{ feature }}
                </li>
                {% endfor %}
              </ul>
              <div class="room-card__footer">
                {% if room.priceLabel %}
                <div class="room-card__price">
                  <span class="room-card__price-amount">{{ room.priceLabel }}</span>
                  <span class="room-card__price-unit">{{ room.priceUnit }}</span>
                </div>
                {% endif %}
                <a href="{{ path('app_contact') }}" class="btn btn--primary btn--sm">Rezervovat</a>
              </div>
            </div>
          </article>
          {% endfor %}
        </div>
```

- [ ] **Step 6: Add uploads gitignore**

Create `public/uploads/.gitignore`:

```gitignore
*
!.gitignore
```

- [ ] **Step 7: Run the test**

Run: `docker compose exec -T php php bin/phpunit tests/Controller/AccommodationControllerTest.php`
Expected: PASS.

- [ ] **Step 8: Full suite + manual smoke**

Run:
```bash
docker compose exec -T php php bin/phpunit
curl -sk -o /dev/null -w '%{http_code}\n' https://localhost/ubytovani
```
Expected: all tests green; `/ubytovani` returns `200` and shows the imported rooms.

- [ ] **Step 9: Commit**

```bash
git add src/Controller/AccommodationController.php templates/accommodation/index.html.twig public/uploads/.gitignore tests/Controller/AccommodationControllerTest.php
git commit -m "feat: render public accommodation rooms from database"
```

---

## Self-Review notes

- **Spec coverage:** Room entity (T2), generic Image entity (T3), GD+Intervention (T1,T5), ImageProcessor full+thumb webp (T5), ImageStorageInterface (T4), UX Dropzone + Stimulus boot (T1,T7,T9), admin shell + menu first item Pokoje (T6,T8), Room CRUD (T8), gallery management (T9), seed command (T10), public DB rendering (T11), migrations via diff (T3), session CSRF (admin forms use `isCsrfTokenValid`/form CSRF), uploads in public/uploads (T4,T11). Testing: ImageProcessor unit (T5), RoomController functional (T8,T9), public page (T11).
- **Deferred (documented, not dropped):** drag-reorder of gallery images (order = upload order via `position`); facilities/views/gallery image attachment (Image shape ready); room detail page (`description` ready).
- **Known follow-up:** the legacy seeded images reuse the full file as their own thumbnail (no separate small variant) — acceptable since they are already optimized webp.
