<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AreaController extends AbstractController
{
    #[Route('/okoli', name: 'app_area')]
    public function index(): Response
    {
        return $this->render('area/index.html.twig');
    }
}
