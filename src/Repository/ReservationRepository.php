<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Room-blocking reservations whose date range intersects the [$from, $to) window.
     *
     * @return Reservation[]
     */
    public function findInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.arrival < :to')
            ->andWhere('r.departure > :from')
            ->andWhere('r.status IN (:blocking)')
            ->setParameter('blocking', ReservationStatus::blocking())
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.arrival', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Room-blocking reservations on $room that overlap [$arrival, $departure) (half-open), excluding $exceptId.
     *
     * @return Reservation[]
     */
    public function findOverlapping(Room $room, \DateTimeImmutable $arrival, \DateTimeImmutable $departure, ?int $exceptId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.room = :room')
            ->andWhere('r.arrival < :departure')
            ->andWhere('r.departure > :arrival')
            ->andWhere('r.status IN (:blocking)')
            ->setParameter('blocking', ReservationStatus::blocking())
            ->setParameter('room', $room)
            ->setParameter('arrival', $arrival)
            ->setParameter('departure', $departure)
            ->orderBy('r.arrival', 'ASC');

        if (null !== $exceptId) {
            $qb->andWhere('r.id != :exceptId')->setParameter('exceptId', $exceptId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Inquiries waiting for approval, oldest first.
     *
     * @return Reservation[]
     */
    public function findPending(): array
    {
        return $this->findBy(['status' => ReservationStatus::Pending], ['createdAt' => 'ASC']);
    }

    /**
     * Confirmed reservations arriving in [$from, $to), soonest first.
     *
     * @return Reservation[]
     */
    public function findConfirmedArrivals(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :confirmed')
            ->andWhere('r.arrival >= :from')
            ->andWhere('r.arrival < :to')
            ->setParameter('confirmed', ReservationStatus::Confirmed)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.arrival', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
