<?php

namespace App\Controller;

use App\Repository\MassageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WellnessController extends AbstractController
{
    #[Route('/wellness', name: 'app_wellness')]
    public function index(MassageRepository $massages): Response
    {
        return $this->render('wellness/index.html.twig', [
            'massages' => $massages->findAllOrdered(),
        ]);
    }
}
