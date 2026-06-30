<?php

namespace App\Tests\Controller;

use App\Entity\Meal;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PricingControllerTest extends WebTestCase
{
    public function testMealsRenderedFromDatabase(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        foreach (['mealrow-test', 'mealcard-test'] as $name) {
            $existing = $em->getRepository(Meal::class)->findOneBy(['name' => $name]);
            if ($existing) {
                $em->remove($existing);
            }
        }
        $em->flush();

        $row = (new Meal())->setName('mealrow-test')->setPrice(199)->setPosition(90);
        $card = (new Meal())->setName('mealcard-test')->setPrice(888)->setPriceUnit('/den')
            ->setNote('Týdně 3 000 Kč')->setHighlighted(true)->setFeatures(['Odrážka ABC'])->setPosition(91);
        $em->persist($row);
        $em->persist($card);
        $em->flush();

        try {
            $client->request('GET', '/cenik');
            self::assertResponseIsSuccessful();
            $html = str_replace("\u{00a0}", ' ', (string) $client->getResponse()->getContent());
            // Non-highlighted meal renders as a table row (with name).
            self::assertStringContainsString('mealrow-test', $html);
            self::assertStringContainsString('199 Kč', $html);
            // Highlighted meal renders as a card (price + note + bullets; no name, matches design).
            self::assertStringContainsString('888 Kč', $html);
            self::assertStringContainsString('Týdně 3 000 Kč', $html);
            self::assertStringContainsString('Odrážka ABC', $html);
        } finally {
            $em->clear();
            foreach (['mealrow-test', 'mealcard-test'] as $name) {
                $leftover = $em->getRepository(Meal::class)->findOneBy(['name' => $name]);
                if ($leftover) {
                    $em->remove($leftover);
                }
            }
            $em->flush();
        }
    }

    public function testRoomTableRenderedFromDatabase(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $existing = $em->getRepository(Room::class)->findOneBy(['slug' => 'cenik-test']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }
        $room = (new Room())
            ->setName('Ceníkový pokoj QWE')
            ->setSlug('cenik-test')
            ->setPrice(1234)
            ->setPriceUnit('/ noc')
            ->setPriceSingleNight(1567)
            ->setFeatures(['Vlastní koupelna', 'Balkon']);
        $em->persist($room);
        $em->flush();

        try {
            $client->request('GET', '/cenik');
            self::assertResponseIsSuccessful();
            // Normalize non-breaking spaces (Czech typography) to plain spaces for assertions.
            $html = str_replace("\u{00a0}", ' ', (string) $client->getResponse()->getContent());
            self::assertStringContainsString('Ceníkový pokoj QWE', $html);
            self::assertStringContainsString('1 234 Kč', $html);   // 2+ nocí
            self::assertStringContainsString('1 567 Kč', $html);   // 1 noc
            self::assertStringContainsString('Vlastní koupelna, Balkon', $html); // features joined
        } finally {
            $em->clear();
            $leftover = $em->getRepository(Room::class)->findOneBy(['slug' => 'cenik-test']);
            if ($leftover) {
                $em->remove($leftover);
                $em->flush();
            }
        }
    }
}
