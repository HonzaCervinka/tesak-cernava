<?php

namespace App\Service\Reservation;

use App\DTO\BookingRequest;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/** Turns a public inquiry into a pending reservation, refusing dates that are already taken. */
final readonly class ReservationBooker
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReservationRepository $reservations,
    ) {}

    /**
     * @throws RoomUnavailableException when the room is gone or the dates got taken meanwhile
     */
    public function book(BookingRequest $request): Reservation
    {
        // Not wrapInTransaction(): that closes the EntityManager on any exception, including our own "taken" refusal.
        $connection = $this->em->getConnection();
        $connection->beginTransaction();
        try {
            // Row lock on the room serialises concurrent inquiries for it, so two guests can't grab the same nights.
            $room = $this->em->find(Room::class, $request->roomId, LockMode::PESSIMISTIC_WRITE);
            if (null === $room) {
                throw new RoomUnavailableException('Pokoj neexistuje.');
            }
            if ([] !== $this->reservations->findOverlapping($room, $request->arrival, $request->departure)) {
                throw new RoomUnavailableException('Termín je už obsazený.');
            }

            $reservation = (new Reservation())
                ->setRoom($room)
                ->setStatus(ReservationStatus::Pending)
                ->setArrival($request->arrival)
                ->setDeparture($request->departure)
                ->setGuestName($request->name)
                ->setEmail($request->email)
                ->setPhone($request->phone)
                ->setGuests($request->guests)
                ->setNote($request->note);
            $this->em->persist($reservation);
            $this->em->flush();
            $connection->commit();

            return $reservation;
        } catch (\Throwable $e) {
            $connection->rollBack();

            throw $e;
        }
    }
}
