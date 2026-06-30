<?php

namespace App\Controller\Admin;

use App\Entity\Massage;
use App\Form\MassageType;
use App\Repository\MassageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/massages')]
#[IsGranted('ROLE_ADMIN')]
final class MassageController extends AbstractController
{
    #[Route('', name: 'app_admin_massages_index', methods: ['GET'])]
    public function index(MassageRepository $massages): Response
    {
        return $this->render('admin/massage/index.html.twig', [
            'massages' => $massages->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_admin_massages_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $massage = new Massage();
        $form = $this->createForm(MassageType::class, $massage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($massage);
            $em->flush();
            $this->addFlash('success', 'Masáž vytvořena.');

            return $this->redirectToRoute('app_admin_massages_index');
        }

        return $this->render('admin/massage/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_admin_massages_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Massage $massage, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MassageType::class, $massage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Masáž uložena.');

            return $this->redirectToRoute('app_admin_massages_index');
        }

        return $this->render('admin/massage/edit.html.twig', ['form' => $form, 'massage' => $massage]);
    }

    #[Route('/{id}/delete', name: 'app_admin_massages_delete', methods: ['POST'])]
    public function delete(Request $request, Massage $massage, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$massage->getId(), (string) $request->request->get('_token'))) {
            $em->remove($massage);
            $em->flush();
            $this->addFlash('success', 'Masáž smazána.');
        }

        return $this->redirectToRoute('app_admin_massages_index');
    }
}
