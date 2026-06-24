<?php

namespace App\Controller\Admin;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rooms')]
#[IsGranted('ROLE_ADMIN')]
final class RoomController extends AbstractController
{
    #[Route('', name: 'app_admin_rooms_index', methods: ['GET'])]
    public function index(RoomRepository $rooms): Response
    {
        return $this->render('admin/room/index.html.twig', [
            'rooms' => $rooms->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_admin_rooms_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($room);
            $em->flush();
            $this->addFlash('success', 'Pokoj vytvořen.');

            return $this->redirectToRoute('app_admin_rooms_index');
        }

        return $this->render('admin/room/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_admin_rooms_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Room $room, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Pokoj uložen.');

            return $this->redirectToRoute('app_admin_rooms_index');
        }

        return $this->render('admin/room/edit.html.twig', ['form' => $form, 'room' => $room]);
    }

    #[Route('/{id}/delete', name: 'app_admin_rooms_delete', methods: ['POST'])]
    public function delete(Request $request, Room $room, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), (string) $request->request->get('_token'))) {
            $em->remove($room);
            $em->flush();
            $this->addFlash('success', 'Pokoj smazán.');
        }

        return $this->redirectToRoute('app_admin_rooms_index');
    }
}
