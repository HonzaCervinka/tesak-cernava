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
