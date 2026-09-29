<?php

namespace App\Tests\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingControllerTest extends WebTestCase
{
    private const SLUG = 'booking-test';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Room $room;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanup();
        $this->room = (new Room())->setName('Test booking')->setSlug(self::SLUG)->setCapacity(3);
        $this->em->persist($this->room);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        try {
            $this->cleanup();
        } finally {
            parent::tearDown();
        }
    }

    private function cleanup(): void
    {
        $this->em->clear();
        $room = $this->em->getRepository(Room::class)->findOneBy(['slug' => self::SLUG]);
        if (!$room) {
            return;
        }
        foreach ($this->em->getRepository(Reservation::class)->findBy(['room' => $room]) as $res) {
            $this->em->remove($res);
        }
        $this->em->flush();
        $this->em->remove($room);
        $this->em->flush();
    }

    private function day(int $offset): string
    {
        return (new \DateTimeImmutable('today'))->modify(sprintf('%+d days', $offset))->format('Y-m-d');
    }

    private function reserve(int $from, int $to, ReservationStatus $status): void
    {
        $this->em->persist((new Reservation())
            ->setRoom($this->room)->setGuestName('Host')->setStatus($status)
            ->setArrival(new \DateTimeImmutable($this->day($from)))
            ->setDeparture(new \DateTimeImmutable($this->day($to))));
        $this->em->flush();
    }

    /** @param array<string, mixed> $override */
    private function submit(array $override = []): void
    {
        $this->client->request('POST', '/api/reservations', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($override + [
            'roomId' => $this->room->getId(),
            'arrival' => $this->day(10),
            'departure' => $this->day(12),
            'name' => 'Jana Poptávková',
            'email' => 'jana@example.com',
            'phone' => '777111222',
            'guests' => '2',
            'note' => 'Přijedeme večer',
            'consent' => true,
            'website' => '',
        ]));
    }

    private function inquiries(): array
    {
        $this->em->clear();

        return $this->em->getRepository(Reservation::class)->findBy(['room' => $this->room->getId(), 'guestName' => 'Jana Poptávková']);
    }

    public function testAccommodationPageRendersTimeline(): void
    {
        $crawler = $this->client->request('GET', '/ubytovani');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#rezervace[data-rooms]'));
        self::assertGreaterThan(0, $crawler->filter('[data-book-room]')->count());
    }

    public function testAvailabilityListsBlockingReservationsWithoutGuestDetails(): void
    {
        $this->reserve(2, 4, ReservationStatus::Confirmed);
        $this->reserve(5, 7, ReservationStatus::Pending);
        $this->reserve(8, 9, ReservationStatus::Rejected);

        $this->client->request('GET', '/api/availability?days=14');

        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($this->day(0), $body['from']);
        self::assertSame([
            [$this->day(2), $this->day(4)],
            [$this->day(5), $this->day(7)],
        ], $body['busy'][$this->room->getId()]);
        self::assertStringNotContainsString('Host', $this->client->getResponse()->getContent());
    }

    public function testAvailabilityClampsPastStartToToday(): void
    {
        $this->client->request('GET', '/api/availability?from=2020-01-01&days=999');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($this->day(0), $body['from']);
        self::assertSame(62, $body['days']);
        self::assertSame($this->day(0), $body['today']);
    }

    public function testInquiryCreatesPendingReservationAndSendsEmails(): void
    {
        $this->submit();

        self::assertResponseStatusCodeSame(201);
        self::assertQueuedEmailCount(2);
        $found = $this->inquiries();
        self::assertCount(1, $found);
        self::assertSame(ReservationStatus::Pending, $found[0]->getStatus());
        self::assertSame(2, $found[0]->getGuests());
        self::assertSame('jana@example.com', $found[0]->getEmail());
    }

    public function testInquiryIntoTakenDatesIsRefused(): void
    {
        $this->reserve(11, 14, ReservationStatus::Pending);

        $this->submit();

        self::assertResponseStatusCodeSame(409);
        self::assertCount(0, $this->inquiries());
    }

    public function testDepartureDayOfAnotherGuestIsFreeForArrival(): void
    {
        $this->reserve(7, 10, ReservationStatus::Confirmed);

        $this->submit();

        self::assertResponseStatusCodeSame(201);
    }

    public function testInvalidInquiryReturnsFieldErrors(): void
    {
        $this->submit(['name' => '', 'email' => 'nope', 'guests' => '5', 'consent' => false]);

        self::assertResponseStatusCodeSame(422);
        $errors = json_decode($this->client->getResponse()->getContent(), true)['errors'];
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('consent', $errors);
        self::assertArrayHasKey('guests', $errors); // capacity is 3
        self::assertCount(0, $this->inquiries());
    }

    public function testPastArrivalIsRejected(): void
    {
        $this->submit(['arrival' => $this->day(-2), 'departure' => $this->day(1)]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testHoneypotPretendsSuccessButSavesNothing(): void
    {
        $this->submit(['website' => 'http://spam.example']);

        self::assertResponseStatusCodeSame(201);
        self::assertCount(0, $this->inquiries());
        self::assertQueuedEmailCount(0);
    }
}
