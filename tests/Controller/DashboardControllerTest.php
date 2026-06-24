<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardControllerTest extends WebTestCase
{
    public function testAnonymousRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/admin/login');
    }

    public function testAdminCanSeeDashboard(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())->setEmail('test-admin@example.com')->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'pw'));
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);
        try {
            $client->request('GET', '/admin');

            self::assertResponseIsSuccessful();
        } finally {
            $em->remove($user);
            $em->flush();
        }
    }
}
