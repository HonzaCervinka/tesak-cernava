<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrivacyController extends AbstractController
{
    #[Route('/zasady-ochrany-osobnich-udaju', name: 'app_privacy')]
    public function index(): Response
    {
        return $this->render('privacy/index.html.twig');
    }
}
