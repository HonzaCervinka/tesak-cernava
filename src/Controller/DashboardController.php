<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    private const UPCOMING_DAYS = 14;

    #[Route('/admin', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(ReservationRepository $reservations): Response
    {
        $today = new \DateTimeImmutable('today');

        return $this->render('dashboard/index.html.twig', [
            'pending' => $reservations->findPending(),
            'arrivals' => $reservations->findConfirmedArrivals($today, $today->modify('+'.self::UPCOMING_DAYS.' days')),
            'upcomingDays' => self::UPCOMING_DAYS,
            'today' => $today,
        ]);
    }
}
