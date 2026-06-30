<?php

namespace App\Tests\Controller;

use App\Entity\Massage;
use App\Entity\MassagePrice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WellnessControllerTest extends WebTestCase
{
    public function testMassageRenderedFromDatabase(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $existing = $em->getRepository(Massage::class)->findOneBy(['name' => 'Wellness test masáž ZXC']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }
        $massage = (new Massage())->setName('Wellness test masáž ZXC')->setPosition(99);
        $massage->addPrice((new MassagePrice())->setPrice(555)->setMinutes(45));
        $em->persist($massage);
        $em->flush();

        try {
            $client->request('GET', '/wellness');
            self::assertResponseIsSuccessful();
            $html = str_replace("\u{00a0}", ' ', (string) $client->getResponse()->getContent());
            self::assertStringContainsString('Wellness test masáž ZXC', $html);
            self::assertStringContainsString('555 Kč', $html);
            self::assertStringContainsString('45 min', $html);
        } finally {
            $em->clear();
            $leftover = $em->getRepository(Massage::class)->findOneBy(['name' => 'Wellness test masáž ZXC']);
            if ($leftover) {
                $em->remove($leftover);
                $em->flush();
            }
        }
    }
}
