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
