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

    public function testCapacityDefaultsToZeroAndIsSettable(): void
    {
        $room = new Room();
        self::assertSame(0, $room->getCapacity());
        self::assertSame(4, $room->setCapacity(4)->getCapacity());
    }

    public function testReservationsCollectionStartsEmpty(): void
    {
        self::assertCount(0, (new Room())->getReservations());
    }
}
