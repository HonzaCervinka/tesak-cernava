<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(RoomRepository $rooms): Response
    {
        return $this->render('home/index.html.twig', [
            'rooms' => $rooms->findForHomepage(),
        ]);
    }
}
