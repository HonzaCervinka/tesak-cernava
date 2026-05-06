<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WarmController extends AbstractController
{
    #[Route('/warm', name: 'app_warm')]
    public function index(): Response
    {
        return $this->render('warm/index.html.twig', [
            'controller_name' => 'WarmController',
        ]);
    }
}
