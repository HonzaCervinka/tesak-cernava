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
