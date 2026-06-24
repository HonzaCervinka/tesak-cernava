<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use App\Service\Image\ImageProcessor;
use App\Service\Image\ImageStorageInterface;
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

    #[Route('/{id}/images', name: 'app_admin_rooms_images_upload', methods: ['POST'])]
    public function uploadImages(Request $request, Room $room, ImageProcessor $processor, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('upload'.$room->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $position = $room->getImages()->count();
        $makeMain = $room->getImages()->isEmpty();
        foreach ($request->files->all()['images'] ?? [] as $file) {
            if (null === $file) {
                continue;
            }
            $image = $processor->process($file, 'rooms/'.$room->getSlug());
            $image->setPosition($position++);
            if ($makeMain) {
                $image->setIsMain(true);
                $makeMain = false;
            }
            $room->addImage($image);
            $em->persist($image);
        }
        $em->flush();
        $this->addFlash('success', 'Fotky nahrány.');

        return $this->redirectToRoute('app_admin_rooms_edit', ['id' => $room->getId()]);
    }

    #[Route('/{id}/images/{imageId}/delete', name: 'app_admin_rooms_images_delete', methods: ['POST'])]
    public function deleteImage(Request $request, Room $room, int $imageId, ImageStorageInterface $storage, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('img-delete'.$imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($room->getImages() as $image) {
            if ($image->getId() === $imageId) {
                $storage->delete($image->getFilename());
                $storage->delete($image->getThumbnail());
                $room->removeImage($image);
                $em->remove($image);
                break;
            }
        }
        $em->flush();
        $this->addFlash('success', 'Fotka smazána.');

        return $this->redirectToRoute('app_admin_rooms_edit', ['id' => $room->getId()]);
    }

    #[Route('/{id}/images/{imageId}/main', name: 'app_admin_rooms_images_main', methods: ['POST'])]
    public function setMainImage(Request $request, Room $room, int $imageId, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('img-main'.$imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($room->getImages() as $image) {
            $image->setIsMain($image->getId() === $imageId);
        }
        $em->flush();
        $this->addFlash('success', 'Hlavní fotka nastavena.');

        return $this->redirectToRoute('app_admin_rooms_edit', ['id' => $room->getId()]);
    }
}
