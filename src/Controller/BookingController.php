<?php

namespace App\Controller;

use App\DTO\BookingRequest;
use App\Entity\Room;
use App\Repository\ReservationRepository;
use App\Service\Reservation\ReservationBooker;
use App\Service\Reservation\ReservationMailer;
use App\Service\Reservation\RoomUnavailableException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Public availability timeline on /ubytovani: occupancy feed and inquiry submission. */
final class BookingController extends AbstractController
{
    private const MAX_DAYS = 62;

    /** Occupied date ranges per room – no guest details, the timeline only needs free/busy. */
    #[Route('/api/availability', name: 'api_availability', methods: ['GET'])]
    public function availability(Request $request, ReservationRepository $reservations): JsonResponse
    {
        $today = new \DateTimeImmutable('today');
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->query->get('from'));
        if (!$from instanceof \DateTimeImmutable || $from < $today) {
            $from = $today;
        }
        $days = max(1, min(self::MAX_DAYS, $request->query->getInt('days', 14)));
        // One night before the window, so the timeline can draw a half-busy first day.
        $to = $from->modify('+'.$days.' days');

        $busy = [];
        foreach ($reservations->findInRange($from->modify('-1 day'), $to) as $res) {
            $busy[$res->getRoom()->getId()][] = [$res->getArrival()->format('Y-m-d'), $res->getDeparture()->format('Y-m-d')];
        }

        return $this->json(['today' => $today->format('Y-m-d'), 'from' => $from->format('Y-m-d'), 'days' => $days, 'busy' => $busy]);
    }

    #[Route('/api/reservations', name: 'api_reservations_create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        ReservationBooker $booker,
        ReservationMailer $mailer,
    ): JsonResponse {
        $booking = BookingRequest::fromArray(json_decode($request->getContent(), true) ?? []);

        if ($booking->isSpam()) {
            return $this->json(['message' => 'OK'], 201);
        }

        $errors = [];
        foreach ($validator->validate($booking) as $violation) {
            $errors[$violation->getPropertyPath()] ??= $violation->getMessage();
        }
        $room = $em->find(Room::class, $booking->roomId);
        if (null === $room) {
            $errors['roomId'] = 'Vyberte pokoj.';
        } elseif ($room->getCapacity() > 0 && null !== $booking->guests && $booking->guests > $room->getCapacity()) {
            $errors['guests'] = sprintf('Pokoj je pro nejvýše %d osob.', $room->getCapacity());
        }
        if ([] !== $errors) {
            return $this->json(['errors' => $errors], 422);
        }

        try {
            $reservation = $booker->book($booking);
        } catch (RoomUnavailableException) {
            return $this->json(['error' => 'Tento termín mezitím někdo obsadil. Vyberte prosím jiný.'], 409);
        }

        $mailer->sendInquiry($reservation);

        return $this->json(['message' => 'OK'], 201);
    }
}
