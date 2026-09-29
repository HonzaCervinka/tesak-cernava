<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ReservationControllerTest extends WebTestCase
{
    private function loginAdmin(KernelBrowser $client): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'res-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('res-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
            $em->flush();
        }
        $client->loginUser($user);
    }

    private function makeRoom(EntityManagerInterface $em, string $slug): Room
    {
        $room = (new Room())->setName('Test '.$slug)->setSlug($slug)->setCapacity(4);
        $em->persist($room);
        $em->flush();

        return $room;
    }

    private function cleanupRoom(EntityManagerInterface $em, string $slug): void
    {
        $em->clear();
        $room = $em->getRepository(Room::class)->findOneBy(['slug' => $slug]);
        if (!$room) {
            return;
        }
        foreach ($em->getRepository(Reservation::class)->findBy(['room' => $room]) as $res) {
            $em->remove($res);
        }
        $em->flush();
        $em->remove($room);
        $em->flush();
    }

    public function testAnonymousBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/reservations');
        self::assertResponseRedirects('/admin/login');
    }

    public function testIndexLoadsForAdmin(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $client->request('GET', '/admin/reservations');
        self::assertResponseIsSuccessful();
    }

    public function testCreateReservation(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-create';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        try {
            $crawler = $client->request('GET', '/admin/reservations/new');
            self::assertResponseIsSuccessful();
            $token = $crawler->filter('input[name="reservation[_token]"]')->attr('value');

            $client->request('POST', '/admin/reservations/new', [
                'reservation' => [
                    'room' => $room->getId(),
                    'guestName' => 'Novák',
                    'arrival' => '2026-07-03',
                    'departure' => '2026-07-06',
                    'guests' => 2,
                    'phone' => '777123456',
                    'email' => 'novak@example.com',
                    'note' => 'pozdní příjezd',
                    '_token' => $token,
                ],
            ]);
            self::assertResponseRedirects('/admin/reservations');

            $em->clear();
            $found = $em->getRepository(Reservation::class)->findOneBy(['guestName' => 'Novák']);
            self::assertNotNull($found);
            self::assertSame(2, $found->getGuests());
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testDepartureBeforeArrivalIsRejected(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-invalid';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        try {
            $crawler = $client->request('GET', '/admin/reservations/new');
            $token = $crawler->filter('input[name="reservation[_token]"]')->attr('value');

            $client->request('POST', '/admin/reservations/new', [
                'reservation' => [
                    'room' => $room->getId(),
                    'guestName' => 'Chyba',
                    'arrival' => '2026-07-10',
                    'departure' => '2026-07-08',
                    '_token' => $token,
                ],
            ]);
            self::assertResponseStatusCodeSame(422);

            $em->clear();
            self::assertNull($em->getRepository(Reservation::class)->findOneBy(['guestName' => 'Chyba']));
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testOverlapWarnsButSaves(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-overlap';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        $existing = (new Reservation())
            ->setRoom($room)->setGuestName('První')
            ->setArrival(new \DateTimeImmutable('2026-07-03'))
            ->setDeparture(new \DateTimeImmutable('2026-07-06'));
        $em->persist($existing);
        $em->flush();

        try {
            $crawler = $client->request('GET', '/admin/reservations/new');
            $token = $crawler->filter('input[name="reservation[_token]"]')->attr('value');

            $client->request('POST', '/admin/reservations/new', [
                'reservation' => [
                    'room' => $room->getId(),
                    'guestName' => 'Druhý',
                    'arrival' => '2026-07-04',
                    'departure' => '2026-07-08',
                    '_token' => $token,
                ],
            ]);
            self::assertResponseRedirects('/admin/reservations');
            $crawler = $client->followRedirect();
            self::assertGreaterThan(0, $crawler->filter('.flash--warning')->count());

            $em->clear();
            self::assertNotNull($em->getRepository(Reservation::class)->findOneBy(['guestName' => 'Druhý']));
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testDeleteReservation(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-delete';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        $res = (new Reservation())
            ->setRoom($room)->setGuestName('Smazat')
            ->setArrival(new \DateTimeImmutable('2026-07-03'))
            ->setDeparture(new \DateTimeImmutable('2026-07-06'));
        $em->persist($res);
        $em->flush();
        $id = $res->getId();

        try {
            $crawler = $client->request('GET', '/admin/reservations/'.$id.'/edit');
            self::assertResponseIsSuccessful();
            $token = $crawler->filter('input[name="_token"]')->attr('value');

            $client->request('POST', '/admin/reservations/'.$id.'/delete', ['_token' => $token]);
            self::assertResponseRedirects('/admin/reservations');

            $em->clear();
            self::assertNull($em->getRepository(Reservation::class)->find($id));
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testDashboardListsPendingAndConfirmMovesItOut(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-confirm';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        $res = (new Reservation())
            ->setRoom($room)->setGuestName('Poptávka Dvořák')
            ->setArrival(new \DateTimeImmutable('+3 days'))
            ->setDeparture(new \DateTimeImmutable('+5 days'))
            ->setStatus(ReservationStatus::Pending);
        $em->persist($res);
        $em->flush();
        $id = $res->getId();

        try {
            $crawler = $client->request('GET', '/admin');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', 'Poptávka Dvořák');
            $token = $crawler->filter('form[action="/admin/reservations/'.$id.'/confirm"] input[name="_token"]')->attr('value');

            $client->request('POST', '/admin/reservations/'.$id.'/confirm', ['_token' => $token]);
            self::assertResponseRedirects('/admin');

            $em->clear();
            self::assertSame(ReservationStatus::Confirmed, $em->getRepository(Reservation::class)->find($id)->getStatus());

            // Now shown among upcoming arrivals, no longer awaiting approval.
            $crawler = $client->request('GET', '/admin');
            self::assertCount(0, $crawler->filter('form[action="/admin/reservations/'.$id.'/confirm"]'));
            self::assertSelectorTextContains('body', 'Poptávka Dvořák');
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testRejectedReservationFreesTheRoom(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-reject';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        $res = (new Reservation())
            ->setRoom($room)->setGuestName('Zamítnout')
            ->setArrival(new \DateTimeImmutable('2026-08-03'))
            ->setDeparture(new \DateTimeImmutable('2026-08-06'))
            ->setStatus(ReservationStatus::Pending);
        $em->persist($res);
        $em->flush();
        $id = $res->getId();

        try {
            $crawler = $client->request('GET', '/admin');
            $token = $crawler->filter('form[action="/admin/reservations/'.$id.'/reject"] input[name="_token"]')->attr('value');

            $client->request('POST', '/admin/reservations/'.$id.'/reject', ['_token' => $token]);
            self::assertResponseRedirects('/admin');

            $em->clear();
            $room = $em->getRepository(Room::class)->findOneBy(['slug' => $slug]);
            self::assertSame(ReservationStatus::Rejected, $em->getRepository(Reservation::class)->find($id)->getStatus());
            self::assertSame([], $em->getRepository(Reservation::class)->findOverlapping(
                $room, new \DateTimeImmutable('2026-08-04'), new \DateTimeImmutable('2026-08-05'),
            ));
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testConfirmRequiresValidToken(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-token';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        $res = (new Reservation())
            ->setRoom($room)->setGuestName('Bez tokenu')
            ->setArrival(new \DateTimeImmutable('2026-08-03'))
            ->setDeparture(new \DateTimeImmutable('2026-08-06'))
            ->setStatus(ReservationStatus::Pending);
        $em->persist($res);
        $em->flush();
        $id = $res->getId();

        try {
            $client->request('POST', '/admin/reservations/'.$id.'/confirm', ['_token' => 'nope']);

            $em->clear();
            self::assertSame(ReservationStatus::Pending, $em->getRepository(Reservation::class)->find($id)->getStatus());
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }

    public function testStatusChangeInEditFormEmailsGuest(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $slug = 'res-edit-status';
        $this->cleanupRoom($em, $slug);
        $room = $this->makeRoom($em, $slug);

        $res = (new Reservation())
            ->setRoom($room)->setGuestName('Formulář')->setEmail('host@example.com')
            ->setArrival(new \DateTimeImmutable('2026-08-03'))
            ->setDeparture(new \DateTimeImmutable('2026-08-06'))
            ->setStatus(ReservationStatus::Pending);
        $em->persist($res);
        $em->flush();
        $id = $res->getId();

        try {
            // Saving without touching the status sends nothing.
            $crawler = $client->request('GET', '/admin/reservations/'.$id.'/edit');
            $client->submit($crawler->selectButton('Uložit')->form());
            self::assertResponseRedirects('/admin/reservations');
            self::assertQueuedEmailCount(0);

            $crawler = $client->request('GET', '/admin/reservations/'.$id.'/edit');
            $client->submit($crawler->selectButton('Uložit')->form(['reservation[status]' => 'confirmed']));
            self::assertResponseRedirects('/admin/reservations');
            self::assertQueuedEmailCount(1);
            self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'host@example.com');
            self::assertEmailHtmlBodyContains(self::getMailerMessage(), '+420 775 727 306');
            self::assertEmailHtmlBodyContains(self::getMailerMessage(), 'tesakcernava@seznam.cz');

            $em->clear();
            self::assertSame(ReservationStatus::Confirmed, $em->getRepository(Reservation::class)->find($id)->getStatus());
        } finally {
            $this->cleanupRoom($em, $slug);
        }
    }
}
