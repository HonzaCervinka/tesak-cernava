<?php

namespace App\Tests\Controller;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testFlaggedRoomIsRenderedOthersNot(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        foreach (['hp-shown-test', 'hp-hidden-test'] as $slug) {
            $existing = $em->getRepository(Room::class)->findOneBy(['slug' => $slug]);
            if ($existing) {
                $em->remove($existing);
            }
        }
        $em->flush();

        $shown = (new Room())->setName('Domovský pokoj ABC')->setSlug('hp-shown-test')->setShowOnHomepage(true);
        $hidden = (new Room())->setName('Skrytý pokoj XYZ')->setSlug('hp-hidden-test')->setShowOnHomepage(false);
        $em->persist($shown);
        $em->persist($hidden);
        $em->flush();

        try {
            $client->request('GET', '/');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', 'Domovský pokoj ABC');
            $html = (string) $client->getResponse()->getContent();
            self::assertStringNotContainsString('Skrytý pokoj XYZ', $html);
        } finally {
            $em->clear();
            foreach (['hp-shown-test', 'hp-hidden-test'] as $slug) {
                $leftover = $em->getRepository(Room::class)->findOneBy(['slug' => $slug]);
                if ($leftover) {
                    $em->remove($leftover);
                }
            }
            $em->flush();
        }
    }
}
