<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RoomImageTest extends WebTestCase
{
    public function testUploadImageToRoom(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'img-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('img-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
        }
        // Clean up any room left from a previous run so the unique slug constraint doesn't fire.
        $existing = $em->getRepository(Room::class)->findOneBy(['slug' => 'galerie-test']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }
        $room = (new Room())->setName('Galerie')->setSlug('galerie-test')->setPriceUnit('/ noc');
        $em->persist($room);
        $em->flush();
        $client->loginUser($user);

        // Crawl the edit page so the session is started and we can read the CSRF token from the form.
        $crawler = $client->request('GET', '/admin/rooms/'.$room->getId().'/edit');
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value') ?? '';

        // build a temp png upload
        $manager = new ImageManager(new Driver());
        $path = sys_get_temp_dir().'/up-'.bin2hex(random_bytes(4)).'.png';
        $manager->createImage(800, 600)->fill('aabbcc')->save($path);
        $upload = new UploadedFile($path, 'test.png', 'image/png', null, true);

        $client->request(
            'POST',
            '/admin/rooms/'.$room->getId().'/images',
            ['_token' => $csrfToken],
            ['images' => [$upload]],
        );
        self::assertResponseRedirects('/admin/rooms/'.$room->getId().'/edit');

        $em->clear();
        $reloaded = $em->getRepository(Room::class)->find($room->getId());
        self::assertCount(1, $reloaded->getImages());
        @unlink($path);
    }
}
