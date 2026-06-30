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

        try {
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
        } finally {
            @unlink($path);
            $em->clear();
            $leftover = $em->getRepository(Room::class)->find($room->getId());
            if ($leftover) {
                $em->remove($leftover);
            }
            $leftoverUser = $em->getRepository(User::class)->findOneBy(['email' => 'img-admin@example.com']);
            if ($leftoverUser) {
                $em->remove($leftoverUser);
            }
            $em->flush();
        }
    }

    public function testOversizedUploadIsRejectedGracefully(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'badup-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('badup-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
        }
        $existing = $em->getRepository(Room::class)->findOneBy(['slug' => 'badup-test']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }
        $room = (new Room())->setName('BadUpload')->setSlug('badup-test')->setPriceUnit('/ noc');
        $em->persist($room);
        $em->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/admin/rooms/'.$room->getId().'/edit');
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value') ?? '';

        // Simulate a file the PHP upload layer rejected (e.g. exceeds upload_max_filesize):
        // a real path, but error set and test=false -> isValid() === false.
        $manager = new ImageManager(new Driver());
        $path = sys_get_temp_dir().'/bad-'.bin2hex(random_bytes(4)).'.png';
        $manager->createImage(10, 10)->fill('aabbcc')->save($path);
        $bad = new UploadedFile($path, 'huge.jpg', 'image/jpeg', \UPLOAD_ERR_INI_SIZE, false);

        try {
            $client->request(
                'POST',
                '/admin/rooms/'.$room->getId().'/images',
                ['_token' => $csrfToken],
                ['images' => [$bad]],
            );
            self::assertResponseRedirects('/admin/rooms/'.$room->getId().'/edit');

            $em->clear();
            $reloaded = $em->getRepository(Room::class)->find($room->getId());
            self::assertCount(0, $reloaded->getImages(), 'Rejected upload must not create an Image.');
        } finally {
            @unlink($path);
            $em->clear();
            $leftover = $em->getRepository(Room::class)->find($room->getId());
            if ($leftover) {
                $em->remove($leftover);
            }
            $leftoverUser = $em->getRepository(User::class)->findOneBy(['email' => 'badup-admin@example.com']);
            if ($leftoverUser) {
                $em->remove($leftoverUser);
            }
            $em->flush();
        }
    }

    public function testReorderSetsPositionsAndMain(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'reorder-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('reorder-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
        }
        $existing = $em->getRepository(Room::class)->findOneBy(['slug' => 'reorder-test']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }
        $room = (new Room())->setName('Reorder')->setSlug('reorder-test')->setPriceUnit('/ noc');
        $em->persist($room);
        $em->flush();
        $client->loginUser($user);

        // Upload two images via the controller so positions/isMain are set up realistically.
        $crawler = $client->request('GET', '/admin/rooms/'.$room->getId().'/edit');
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value') ?? '';

        $manager = new ImageManager(new Driver());
        $paths = [];
        $uploads = [];
        foreach (['a', 'b'] as $name) {
            $p = sys_get_temp_dir().'/ro-'.$name.'-'.bin2hex(random_bytes(4)).'.png';
            $manager->createImage(800, 600)->fill('aabbcc')->save($p);
            $paths[] = $p;
            $uploads[] = new UploadedFile($p, $name.'.png', 'image/png', null, true);
        }

        try {
            $client->request('POST', '/admin/rooms/'.$room->getId().'/images', ['_token' => $csrfToken], ['images' => $uploads]);
            $em->clear();
            $reloaded = $em->getRepository(Room::class)->find($room->getId());
            $images = $reloaded->getImages()->toArray();
            self::assertCount(2, $images);

            $firstId = $images[0]->getId();
            $secondId = $images[1]->getId();
            self::assertTrue($images[0]->isMain(), 'First uploaded image is the cover initially.');

            // Reorder: put the second image first. Read the CSRF token off the edit page
            // (Stimulus controller attribute) so it matches the session-bound token.
            $crawler = $client->request('GET', '/admin/rooms/'.$room->getId().'/edit');
            $reorderToken = $crawler->filter('[data-gallery-sort-token-value]')->first()->attr('data-gallery-sort-token-value');
            $client->request(
                'POST',
                '/admin/rooms/'.$room->getId().'/images/reorder',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['order' => [$secondId, $firstId], '_token' => $reorderToken]),
            );
            self::assertResponseIsSuccessful();

            $em->clear();
            $second = $em->getRepository(\App\Entity\Image::class)->find($secondId);
            $first = $em->getRepository(\App\Entity\Image::class)->find($firstId);
            self::assertSame(0, $second->getPosition());
            self::assertTrue($second->isMain(), 'New first image becomes the cover.');
            self::assertSame(1, $first->getPosition());
            self::assertFalse($first->isMain());
        } finally {
            foreach ($paths as $p) {
                @unlink($p);
            }
            $em->clear();
            $leftover = $em->getRepository(Room::class)->find($room->getId());
            if ($leftover) {
                $em->remove($leftover);
            }
            $leftoverUser = $em->getRepository(User::class)->findOneBy(['email' => 'reorder-admin@example.com']);
            if ($leftoverUser) {
                $em->remove($leftoverUser);
            }
            $em->flush();
        }
    }
}
