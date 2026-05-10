<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccommodationController extends AbstractController
{
    #[Route('/ubytovani', name: 'app_accommodation')]
    public function index(): Response
    {
        return $this->render('accommodation/index.html.twig');
    }
}
