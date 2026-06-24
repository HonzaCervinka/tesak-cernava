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
