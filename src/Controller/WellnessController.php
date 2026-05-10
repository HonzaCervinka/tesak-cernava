<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WellnessController extends AbstractController
{
    #[Route('/wellness', name: 'app_wellness')]
    public function index(): Response
    {
        return $this->render('wellness/index.html.twig');
    }
}
