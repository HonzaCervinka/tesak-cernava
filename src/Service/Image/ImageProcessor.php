<?php

namespace App\Service\Image;

use App\Entity\Image;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
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

        // Intervention v4 uses decodePath(); honours EXIF orientation on read.
        $full = $this->manager->decodePath($file->getPathname());
        $full->scaleDown(width: self::FULL_WIDTH);
        $fullBytes = (string) $full->encode(new WebpEncoder(self::QUALITY));
        $fullPath = $this->storage->save($fullBytes, "$relativeDir/$basename.webp");

        $thumb = $this->manager->decodePath($file->getPathname());
        $thumb->scaleDown(width: self::THUMB_WIDTH);
        $thumbBytes = (string) $thumb->encode(new WebpEncoder(self::QUALITY));
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
