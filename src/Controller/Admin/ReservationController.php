<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
final class ReservationController extends AbstractController
{
    private const WINDOW_DAYS = 28;

    #[Route('', name: 'app_admin_reservations_index', methods: ['GET'])]
    public function index(Request $request, RoomRepository $rooms, ReservationRepository $reservations): Response
    {
        $from = $this->windowStart($request->query->get('from'));
        $to = $from->modify('+'.self::WINDOW_DAYS.' days');

        $days = [];
        for ($i = 0; $i < self::WINDOW_DAYS; ++$i) {
            $days[] = $from->modify('+'.$i.' days');
        }

        $byRoom = [];
        foreach ($reservations->findInRange($from, $to) as $res) {
            $byRoom[$res->getRoom()->getId()][] = $this->toBar($res, $from);
        }

        $rows = [];
        foreach ($rooms->findAllOrdered() as $room) {
            $rows[] = [
                'room' => $room,
                'bars' => array_filter($byRoom[$room->getId()] ?? []),
            ];
        }

        return $this->render('admin/reservation/index.html.twig', [
            'rows' => $rows,
            'days' => $days,
            'today' => new \DateTimeImmutable('today'),
            'prevFrom' => $from->modify('-'.self::WINDOW_DAYS.' days')->format('Y-m-d'),
            'nextFrom' => $to->format('Y-m-d'),
            'todayFrom' => $this->windowStart(null)->format('Y-m-d'),
        ]);
    }

    #[Route('/new', name: 'app_admin_reservations_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ReservationRepository $reservations, EntityManagerInterface $em): Response
    {
        $reservation = new Reservation();
        $this->prefill($reservation, $request, $em);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reservation);
            $em->flush();
            $this->addFlash('success', 'Rezervace vytvořena.');
            $this->warnOnConflicts($reservation, $reservations);

            return $this->redirectToRoute('app_admin_reservations_index');
        }

        return $this->render('admin/reservation/new.html.twig', ['form' => $form], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/edit', name: 'app_admin_reservations_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, ReservationRepository $reservations, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Rezervace uložena.');
            $this->warnOnConflicts($reservation, $reservations);

            return $this->redirectToRoute('app_admin_reservations_index');
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'form' => $form,
            'reservation' => $reservation,
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/delete', name: 'app_admin_reservations_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Rezervace smazána.');
        }

        return $this->redirectToRoute('app_admin_reservations_index');
    }

    private function windowStart(?string $from): \DateTimeImmutable
    {
        $date = false;
        if (null !== $from && '' !== $from) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        }
        if (!$date instanceof \DateTimeImmutable) {
            $date = new \DateTimeImmutable('today');
        }

        // Align to Monday.
        return $date->modify('monday this week');
    }

    private function prefill(Reservation $reservation, Request $request, EntityManagerInterface $em): void
    {
        $roomId = $request->query->getInt('room');
        if ($roomId > 0 && null !== $room = $em->getRepository(Room::class)->find($roomId)) {
            $reservation->setRoom($room);
        }

        $arrival = $request->query->get('arrival');
        if (\is_string($arrival) && '' !== $arrival) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $arrival);
            if ($date instanceof \DateTimeImmutable) {
                $reservation->setArrival($date);
                $reservation->setDeparture($date->modify('+1 day'));
            }
        }
    }

    private function warnOnConflicts(Reservation $reservation, ReservationRepository $reservations): void
    {
        $room = $reservation->getRoom();
        if (null === $room || null === $reservation->getArrival() || null === $reservation->getDeparture()) {
            return;
        }

        $overlaps = $reservations->findOverlapping($room, $reservation->getArrival(), $reservation->getDeparture(), $reservation->getId());
        foreach ($overlaps as $other) {
            $this->addFlash('warning', sprintf(
                'Překrývá se s: %s (%s–%s).',
                $other->getGuestName(),
                $other->getArrival()->format('j. n.'),
                $other->getDeparture()->format('j. n. Y'),
            ));
        }

        $capacity = $room->getCapacity();
        if ($capacity > 0 && null !== $reservation->getGuests() && $reservation->getGuests() > $capacity) {
            $this->addFlash('warning', sprintf('Počet osob (%d) překračuje kapacitu pokoje (%d).', $reservation->getGuests(), $capacity));
        }
    }

    /** @return array{id:int,guestName:?string,guests:?int,colStart:int,span:int,continuesLeft:bool,continuesRight:bool}|null */
    private function toBar(Reservation $res, \DateTimeImmutable $from): ?array
    {
        $startIdx = $this->dayIndex($from, $res->getArrival());
        $endIdx = $this->dayIndex($from, $res->getDeparture()); // exclusive column

        $visibleStart = max(0, $startIdx);
        $visibleEnd = min(self::WINDOW_DAYS, $endIdx);
        $span = $visibleEnd - $visibleStart;
        if ($span <= 0) {
            return null;
        }

        return [
            'id' => $res->getId(),
            'guestName' => $res->getGuestName(),
            'guests' => $res->getGuests(),
            'colStart' => $visibleStart + 1,
            'span' => $span,
            'continuesLeft' => $startIdx < 0,
            'continuesRight' => $endIdx > self::WINDOW_DAYS,
        ];
    }

    private function dayIndex(\DateTimeImmutable $from, \DateTimeImmutable $date): int
    {
        $diff = $from->diff($date);

        return $diff->invert ? -$diff->days : $diff->days;
    }
}
