<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RoomControllerTest extends WebTestCase
{
    private function loginAdmin(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'room-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('room-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
            $em->flush();
        }
        $client->loginUser($user);
    }

    public function testAnonymousBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/rooms');
        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateRoom(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        // Clean up any room left from a previous run so the unique slug constraint doesn't fire.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $existing = $em->getRepository(Room::class)->findOneBy(['slug' => 'test-pokoj']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $crawler = $client->request('GET', '/admin/rooms/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Uložit')->form([
            'room[name]' => 'Test Pokoj',
            'room[slug]' => 'test-pokoj',
            'room[priceUnit]' => '/ noc',
            'room[price]' => '1234',
            'room[position]' => '5',
        ]);
        try {
            $client->submit($form);
            self::assertResponseRedirects('/admin/rooms');

            $em = static::getContainer()->get(EntityManagerInterface::class);
            $room = $em->getRepository(Room::class)->findOneBy(['slug' => 'test-pokoj']);
            self::assertNotNull($room);
            self::assertSame('Test Pokoj', $room->getName());
        } finally {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $leftover = $em->getRepository(Room::class)->findOneBy(['slug' => 'test-pokoj']);
            if ($leftover) {
                $em->remove($leftover);
                $em->flush();
            }
        }
    }
}
