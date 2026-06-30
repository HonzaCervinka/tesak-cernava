<?php

namespace App\Controller\Admin;

use App\Entity\Meal;
use App\Form\MealType;
use App\Repository\MealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/meals')]
#[IsGranted('ROLE_ADMIN')]
final class MealController extends AbstractController
{
    #[Route('', name: 'app_admin_meals_index', methods: ['GET'])]
    public function index(MealRepository $meals): Response
    {
        return $this->render('admin/meal/index.html.twig', [
            'meals' => $meals->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_admin_meals_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $meal = new Meal();
        $form = $this->createForm(MealType::class, $meal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($meal);
            $em->flush();
            $this->addFlash('success', 'Položka stravování vytvořena.');

            return $this->redirectToRoute('app_admin_meals_index');
        }

        return $this->render('admin/meal/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_admin_meals_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Meal $meal, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MealType::class, $meal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Položka stravování uložena.');

            return $this->redirectToRoute('app_admin_meals_index');
        }

        return $this->render('admin/meal/edit.html.twig', ['form' => $form, 'meal' => $meal]);
    }

    #[Route('/{id}/delete', name: 'app_admin_meals_delete', methods: ['POST'])]
    public function delete(Request $request, Meal $meal, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$meal->getId(), (string) $request->request->get('_token'))) {
            $em->remove($meal);
            $em->flush();
            $this->addFlash('success', 'Položka stravování smazána.');
        }

        return $this->redirectToRoute('app_admin_meals_index');
    }
}
