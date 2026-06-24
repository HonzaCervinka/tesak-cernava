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
        // Note: Intervention v4 uses createImage() instead of v3's create()
        $manager = new ImageManager(new Driver());
        $source = $manager->createImage(2000, 1000)->fill('cccccc');
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
        $full = $saved[substr($image->getFilename(), strlen('/uploads/'))];
        self::assertSame('WEBP', substr($full, 8, 4));

        @unlink($srcPath);
    }
}
