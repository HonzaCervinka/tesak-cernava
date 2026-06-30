<?php

namespace App\Tests\Entity;

use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    private function reservation(string $arrival, string $departure): Reservation
    {
        return (new Reservation())
            ->setArrival(new \DateTimeImmutable($arrival))
            ->setDeparture(new \DateTimeImmutable($departure));
    }

    public function testOverlappingRangesOverlap(): void
    {
        $a = $this->reservation('2026-06-03', '2026-06-06');
        $b = $this->reservation('2026-06-05', '2026-06-08');

        self::assertTrue($a->overlaps($b));
        self::assertTrue($b->overlaps($a));
    }

    public function testBackToBackDoesNotOverlap(): void
    {
        // One guest leaves on the 6th, another arrives the 6th — room is free.
        $a = $this->reservation('2026-06-03', '2026-06-06');
        $b = $this->reservation('2026-06-06', '2026-06-09');

        self::assertFalse($a->overlaps($b));
        self::assertFalse($b->overlaps($a));
    }

    public function testContainedRangeOverlaps(): void
    {
        $a = $this->reservation('2026-06-03', '2026-06-10');
        $b = $this->reservation('2026-06-05', '2026-06-07');

        self::assertTrue($a->overlaps($b));
        self::assertTrue($b->overlaps($a));
    }

    public function testSeparateRangesDoNotOverlap(): void
    {
        $a = $this->reservation('2026-06-03', '2026-06-05');
        $b = $this->reservation('2026-06-10', '2026-06-12');

        self::assertFalse($a->overlaps($b));
        self::assertFalse($b->overlaps($a));
    }

    public function testNightsCountsCalendarDays(): void
    {
        $r = $this->reservation('2026-06-03', '2026-06-06');

        self::assertSame(3, $r->getNights());
    }
}
