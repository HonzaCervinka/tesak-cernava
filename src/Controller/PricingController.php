<?php

namespace App\Controller;

use App\Repository\MassageRepository;
use App\Repository\MealRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PricingController extends AbstractController
{
    #[Route('/cenik', name: 'app_pricing')]
    public function index(RoomRepository $rooms, MassageRepository $massages, MealRepository $meals): Response
    {
        return $this->render('pricing/index.html.twig', [
            'rooms' => $rooms->findAllOrdered(),
            'massages' => $massages->findAllOrdered(),
            'meals' => $meals->findAllOrdered(),
        ]);
    }
}
