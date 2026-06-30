<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Room;
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
     * Reservations whose date range intersects the [$from, $to) window.
     *
     * @return Reservation[]
     */
    public function findInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.arrival < :to')
            ->andWhere('r.departure > :from')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.arrival', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Reservations on $room that overlap [$arrival, $departure) (half-open), excluding $exceptId.
     *
     * @return Reservation[]
     */
    public function findOverlapping(Room $room, \DateTimeImmutable $arrival, \DateTimeImmutable $departure, ?int $exceptId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.room = :room')
            ->andWhere('r.arrival < :departure')
            ->andWhere('r.departure > :arrival')
            ->setParameter('room', $room)
            ->setParameter('arrival', $arrival)
            ->setParameter('departure', $departure)
            ->orderBy('r.arrival', 'ASC');

        if (null !== $exceptId) {
            $qb->andWhere('r.id != :exceptId')->setParameter('exceptId', $exceptId);
        }

        return $qb->getQuery()->getResult();
    }
}
