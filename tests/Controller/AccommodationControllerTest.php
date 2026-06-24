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
