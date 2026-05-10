<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PricingController extends AbstractController
{
    #[Route('/cenik', name: 'app_pricing')]
    public function index(): Response
    {
        return $this->render('pricing/index.html.twig');
    }
}
